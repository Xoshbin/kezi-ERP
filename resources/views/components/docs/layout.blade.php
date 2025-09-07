@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? ($title . ' · ' . config('app.name')) : (__('Docs') . ' · ' . config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])




</head>
<body class="min-h-screen bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100 transition-colors duration-200">
    <a href="#main" class="skip-link bg-orange-600 text-white px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-orange-500">Skip to content</a>

    @include('docs.components.header')

    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-12 py-8">
            <!-- Sidebar -->
            <aside class="hidden lg:block lg:col-span-3" aria-label="Navigation">
                <div class="sticky top-24 max-h-[calc(100vh-6rem)] overflow-y-auto">
                    {{ $sidebar ?? '' }}
                </div>
            </aside>

            <!-- Main Content -->
            <main id="main" class="col-span-1 lg:col-span-6 xl:col-span-6 min-w-0">
                <div class="min-w-0">
                    {{ $slot }}
                </div>
            </main>

            <!-- Table of Contents -->
            <aside class="hidden xl:block xl:col-span-3" aria-label="On this page">
                <div class="sticky top-24 max-h-[calc(100vh-6rem)] overflow-y-auto">
                    {{ $toc ?? '' }}
                </div>
            </aside>
        </div>
    </div>

</body>
</html>

