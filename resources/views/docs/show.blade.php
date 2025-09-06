@php /** @var string $html */ @endphp

<x-docs.layout :title="$title">
    <x-slot:sidebar>
        @include('docs.components.sidebar', ['items' => \App\Services\DocumentationService::make()->list(), 'active' => $slug ?? null])
    </x-slot:sidebar>

    <article class="docs-prose prose prose-slate dark:prose-invert max-w-none">
        <!-- Breadcrumb Navigation -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                @foreach($breadcrumbs as $crumb)
                    <li class="flex items-center">
                        @if($crumb['slug'])
                            <a
                                href="{{ url('/docs/' . $crumb['slug']) }}"
                                class="font-medium transition-colors hover:text-gray-700 dark:hover:text-gray-300"
                            >
                                {{ $crumb['title'] }}
                            </a>
                        @else
                            <span class="font-medium text-gray-900 dark:text-white">{{ $crumb['title'] }}</span>
                        @endif

                        @if(!$loop->last)
                            <svg class="ml-2 h-4 w-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        <!-- Page Header -->
        <header class="mb-8 border-b border-gray-200 pb-8 dark:border-gray-700">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
                {{ $title }}
            </h1>

            @if(isset($description))
                <p class="mt-4 text-xl text-gray-600 dark:text-gray-400">
                    {{ $description }}
                </p>
            @endif
        </header>

        <!-- Main Content -->
        <div class="docs-content">
            {!! $html !!}
        </div>

        <!-- Page Footer -->
        <footer class="mt-16 border-t border-gray-200 pt-8 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <span>Last updated: </span>
                    <time datetime="{{ date('c', $mtime) }}">
                        {{ date('F j, Y', $mtime) }}
                    </time>
                </div>

                <div class="flex items-center gap-4">
                    <a
                        href="https://github.com/your-repo/edit/main/docs/{{ $slug }}.md"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit this page
                    </a>

                    <a
                        href="https://github.com/your-repo/issues/new?title=Docs: {{ urlencode($title) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Report issue
                    </a>
                </div>
            </div>
        </footer>
    </article>

    <x-slot:toc>
        @include('docs.components.toc', ['toc' => $toc])
    </x-slot:toc>
</x-docs.layout>

