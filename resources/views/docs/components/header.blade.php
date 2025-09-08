@php
$isRtl = in_array(app()->getLocale(), ['ar', 'ckb']);
@endphp
<header class="sticky top-0 z-50 w-full border-b border-gray-200 bg-white/80 backdrop-blur-md dark:border-gray-800 dark:bg-gray-950/80">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center gap-8">
                <a href="/docs" class="flex items-center gap-3 text-gray-900 dark:text-white transition-colors hover:text-orange-600 dark:hover:text-orange-400">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-red-600 text-white font-bold text-sm shadow-sm">
                        {{ config('app.name')[0] ?? 'J' }}
                    </div>
                    <span class="text-lg font-semibold">{{ config('app.name') }} Docs</span>
                </a>
            </div>

            <!-- Search -->
            <div class="relative flex-1 max-w-lg mx-8">
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        id="docs-search-input"
                        type="search"
                        placeholder="{{ __('Search documentation...') }}"
                        aria-label="{{ __('Search documentation') }}"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50/50 ps-10 pe-4 py-2.5 text-sm placeholder-gray-500 outline-none transition-all duration-200 focus:border-orange-500 focus:bg-white focus:ring-2 focus:ring-orange-500/20 dark:border-gray-700 dark:bg-gray-900/50 dark:text-white dark:placeholder-gray-400 dark:focus:border-orange-400 dark:focus:bg-gray-900 dark:focus:ring-orange-400/20"
                        autocomplete="off"
                    />
                    <div class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none">
                        <kbd class="hidden sm:inline-flex items-center rounded border border-gray-200 px-1.5 py-0.5 text-xs font-mono text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            ⌘K
                        </kbd>
                    </div>
                </div>
                <div id="docs-search-results" class="absolute top-full mt-2 hidden max-h-96 w-full overflow-auto rounded-lg border border-gray-200 bg-white p-2 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                    <!-- Search results will be populated here -->
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex items-center gap-4">
                <!-- Global Language Selector -->
                <div class="hidden md:block">
                    <label for="global-lang-select" class="sr-only">{{ __('Language') }}</label>
                    <select id="global-lang-select"
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                        <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>English</option>
                        <option value="ckb" {{ app()->getLocale() === 'ckb' ? 'selected' : '' }}>کوردی (سۆرانی)</option>
                        <option value="ar" {{ app()->getLocale() === 'ar' ? 'selected' : '' }}>العربية</option>
                    </select>
                </div>

                <!-- Version Selector -->
                <div class="hidden md:block">
                    <select class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                        <option>v1.0</option>
                        <option>v0.9</option>
                    </select>
                </div>

                <!-- Theme Toggle -->
                <button
                    id="theme-toggle"
                    type="button"
                    class="rounded-md p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                    aria-label="Toggle theme"
                >
                    <svg class="h-5 w-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg class="h-5 w-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                <!-- GitHub Link -->
                <a
                    href="https://github.com/Xoshbin/jmeryar-notebooklm"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="rounded-md p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                    aria-label="View on GitHub"
                >
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                </a>

                <!-- Mobile Menu Button -->
                <button
                    type="button"
                    class="md:hidden rounded-md p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                    aria-label="Open menu"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>

