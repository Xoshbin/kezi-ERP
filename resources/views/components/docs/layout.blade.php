@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? ($title . ' · ' . config('app.name')) : (__('Docs') . ' · ' . config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .docs-prose a.heading-anchor { opacity: 0; margin-left: .5rem; font-size: .875em; }
        .docs-prose h2:hover .heading-anchor, .docs-prose h3:hover .heading-anchor { opacity: .7; }
        .sticky-sidebar { position: sticky; top: 4.5rem; align-self: start; }
        .skip-link { position:absolute; left:-999px; top:auto; width:1px; height:1px; overflow:hidden; }
        .skip-link:focus { position:static; width:auto; height:auto; }
    </style>
</head>
<body class="min-h-full bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
<a href="#main" class="skip-link">{{ __('Skip to content') }}</a>
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 grid grid-cols-12 gap-4">
    <aside class="hidden lg:block col-span-3 sticky-sidebar" aria-label="{{ __('Sections') }}">
        {{ $sidebar ?? '' }}
    </aside>
    <main id="main" class="col-span-12 lg:col-span-6">
        {{ $slot }}
    </main>
    <aside class="hidden xl:block col-span-3 sticky-sidebar" aria-label="{{ __('On this page') }}">
        {{ $toc ?? '' }}
    </aside>
</div>
<script>
    // Defered highlight.js injection (assumes CSS included via app.css theme)
    import('https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/lib/common.min.js').then(mod => {
        document.querySelectorAll('pre code.hljs').forEach((el) => mod.default.highlightElement(el))
    });
</script>
</body>
</html>

