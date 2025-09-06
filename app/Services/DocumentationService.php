<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class DocumentationService
{
    public function __construct(
        protected string $root,
        protected int $ttl,
        /** @var array<int,string> */
        protected array $exclude = [],
    ) {
    }

    public static function make(): self
    {
        $root = config('docs.root', base_path('docs'));
        $ttl = (int) config('docs.cache_ttl', 3600);
        $exclude = (array) config('docs.exclude', []);

        return new self($root, $ttl, $exclude);
    }

    /**
     * List docs as a flat array with minimal metadata (for index/sidebar).
     * Future: return a tree grouped by folders/locales.
     * @return array<int, array{slug:string,title:string,order:int,path:string,mtime:int}>
     */
    public function list(): array
    {
        $files = collect(File::allFiles($this->root))
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), '.md'))
            ->reject(fn ($file) => in_array($file->getFilename(), $this->exclude, true))
            ->values();

        $items = [];
        foreach ($files as $file) {
            $relPath = Str::after($file->getPathname(), rtrim($this->root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
            $slug = Str::of($relPath)->replaceLast('.md', '')->replace(DIRECTORY_SEPARATOR, '/')->toString();
            $parsed = $this->parseFrontMatter($file->getPathname());
            $title = $parsed['title'] ?? $this->inferTitle($file->getPathname());
            $order = (int) ($parsed['order'] ?? config('docs.default_order', 1000));
            $items[] = [
                'slug' => $slug,
                'title' => $title,
                'order' => $order,
                'path' => $file->getPathname(),
                'mtime' => $file->getMTime(),
            ];
        }

        return collect($items)
            ->sortBy(['order', 'title'])
            ->values()
            ->all();
    }

    /**
     * Get a single document by slug.
     * @return array{title:string, html:string, toc:array<int,array{level:int,id:string,text:string}>, breadcrumbs:array<int,array{title:string,slug:?string}>, mtime:int, etag:string}
     * @throws FileNotFoundException
     */
    public function get(string $slug): array
    {
        $path = $this->resolvePathFromSlug($slug);
        if (! $path || ! File::exists($path)) {
            throw new FileNotFoundException("Doc not found for slug: {$slug}");
        }

        $mtime = File::lastModified($path);
        $cacheKey = 'docs:' . md5($path . ':' . $mtime);

        return Cache::remember($cacheKey, $this->ttl, function () use ($path, $slug, $mtime) {
            $raw = File::get($path);
            try {
                $front = YamlFrontMatter::parse($raw);
                $content = $front->body();
                $meta = $front->matter();
            } catch (\Throwable $e) {
                // Gracefully handle files without valid front matter
                Log::warning('Docs: front matter parse failed', ['path' => $path, 'e' => $e->getMessage()]);
                $content = $raw;
                $meta = [];
            }

            $converter = $this->markdownConverter();
            $html = (string) $converter->convert($content);

            // Inject heading IDs and build TOC from h2/h3
            [$htmlWithIds, $toc] = $this->injectHeadingIdsAndToc($html);

            // Enhance links and images
            $htmlWithLinks = $this->postProcessLinksAndImages($htmlWithIds, $slug);

            $title = $meta['title'] ?? $this->extractH1($htmlWithLinks) ?? $this->inferTitle($path);

            $breadcrumbs = [
                ['title' => __('Docs'), 'slug' => null],
                ['title' => $title, 'slug' => $slug],
            ];

            $etag = 'W/"' . substr(sha1($path . '|' . $mtime . '|' . strlen($htmlWithLinks)), 0, 27) . '"';

            return [
                'title' => $title,
                'html' => $htmlWithLinks,
                'toc' => $toc,
                'breadcrumbs' => $breadcrumbs,
                'mtime' => $mtime,
                'etag' => $etag,
            ];
        });
    }

    /** Build a lightweight search index (title + headings + plain text excerpt). */
    /** @return array<int, array{slug:string,title:string,headings:array<int,string>,excerpt:string}> */
    public function buildIndex(): array
    {
        return collect($this->list())->map(function ($item) {
            $data = $this->get($item['slug']);
            return [
                'slug' => $item['slug'],
                'title' => $data['title'],
                'headings' => collect($data['toc'])->pluck('text')->values()->all(),
                'excerpt' => Str::limit(trim(strip_tags($data['html'])), 260),
            ];
        })->values()->all();
    }

    protected function markdownConverter(): MarkdownConverter
    {
        $env = new Environment([
            'heading_permalink' => [
                'symbol' => '#',
            ],
        ]);
        $env->addExtension(new CommonMarkCoreExtension());
        $env->addExtension(new GithubFlavoredMarkdownExtension());

        return new MarkdownConverter($env);
    }

    /** @return array<string, mixed> */
    protected function parseFrontMatter(string $path): array
    {
        try {
            $raw = File::get($path);
            $front = YamlFrontMatter::parse($raw);
            return $front->matter();
        } catch (\Throwable $e) {
            Log::warning('Failed to parse front matter', ['path' => $path, 'e' => $e->getMessage()]);
            return [];
        }
    }

    protected function inferTitle(string $path): string
    {
        $raw = File::get($path);
        if (preg_match('/^\s*#\s+(.+)$/m', $raw, $m)) {
            return trim($m[1]);
        }

        return Str::of(basename($path, '.md'))->headline()->toString();
    }

    protected function extractH1(string $html): ?string
    {
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html, $m)) {
            return trim(strip_tags($m[1]));
        }
        return null;
    }

    /**
     * @return array{0:string,1:array<int,array{level:int,id:string,text:string}>}
     */
    protected function injectHeadingIdsAndToc(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($dom);

        $toc = [];
        foreach (['h2', 'h3'] as $tag) {
            $nodes = $xpath->query('//' . $tag);
            if (! $nodes) continue;
                foreach ($nodes as $node) {
                $text = trim($node->textContent ?? '');
                if ($text === '') continue;
                $id = Str::slug($text);
                if ($node instanceof \DOMElement) {
                    $node->setAttribute('id', $id);
                }
                // Add anchor link inside the heading
                $a = $dom->createElement('a', '#');
                $a->setAttribute('href', '#' . $id);
                $a->setAttribute('class', 'heading-anchor');
                $node->appendChild($a);

                $toc[] = [
                    'level' => $tag === 'h2' ? 2 : 3,
                    'id' => $id,
                    'text' => $text,
                ];
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $innerHtml = '';
        if ($body instanceof \DOMElement) {
            foreach ($body->childNodes as $child) {
                $innerHtml .= $dom->saveHTML($child);
            }
        }

        return [$innerHtml, $toc];
    }

    protected function postProcessLinksAndImages(string $html, string $currentSlug): string
    {
        // Add rel/target to external links and rewrite relative doc links
        $html = preg_replace_callback('/<a\s+([^>]*href=\"[^\"]+\"[^>]*)>/i', function ($m) use ($currentSlug) {
            $tag = $m[0];
            if (preg_match('/href=\"([^\"]+)\"/i', $tag, $hrefMatch)) {
                $href = $hrefMatch[1];
                if (Str::startsWith($href, ['http://', 'https://'])) {
                    // external
                    if (! str_contains($tag, 'target=')) {
                        $tag = str_replace('<a ', '<a target="_blank" rel="noopener" ', $tag);
                    }
                } elseif (! Str::startsWith($href, '#')) {
                    // relative doc link, normalize
                    $resolved = $this->resolveRelativeDocLink($href, $currentSlug);
                    $tag = str_replace($hrefMatch[0], 'href="' . e($resolved) . '"', $tag);
                }
            }
            return $tag;
        }, $html) ?? $html;

        // Images: make src absolute to /storage or /docs assets path if needed
        $html = preg_replace_callback('/<img\s+([^>]*src=\"[^\"]+\"[^>]*)>/i', function ($m) use ($currentSlug) {
            $tag = $m[0];
            if (preg_match('/src=\"([^\"]+)\"/i', $tag, $srcMatch)) {
                $src = $srcMatch[1];
                if (! Str::startsWith($src, ['http://', 'https://', '/'])) {
                    $resolved = $this->resolveRelativeDocLink($src, $currentSlug);
                    $tag = str_replace($srcMatch[0], 'src="' . e($resolved) . '"', $tag);
                }
            }
            return $tag;
        }, $html) ?? $html;

        // Add highlight.js class to code blocks (append if class exists, otherwise add)
        $html = preg_replace('/<pre><code\s+class=\"([^\"]*)\"/i', '<pre><code class=\"$1 hljs\"', $html) ?? $html;
        $html = preg_replace('/<pre><code(?![^>]*class=)/i', '<pre><code class=\"hljs\"', $html) ?? $html;

        return $html;
    }

    protected function resolvePathFromSlug(string $slug): ?string
    {
        $candidate = $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $slug) . '.md';
        if (File::exists($candidate)) return $candidate;

        // Future: locale-aware resolution like docs/{locale}/{slug}.md
        return null;
    }

    protected function resolveRelativeDocLink(string $href, string $currentSlug): string
    {
        // Resolve relative path against current slug path
        $currentDir = Str::of($currentSlug)->contains('/') ? Str::beforeLast($currentSlug, '/') : '';
        $target = ltrim($href, './');
        if (Str::endsWith($target, '.md')) {
            $target = Str::replaceLast('.md', '', $target);
        }
        $slug = trim($currentDir ? ($currentDir . '/' . $target) : $target, '/');
        return url('/docs/' . $slug);
    }
}

