@php
/** @var array<int,array{slug:string,title:string}> $items */

// Group items by category for better organization
$groupedItems = collect($items)->groupBy(function($item) {
    $parts = explode('/', $item['slug']);
    return count($parts) > 1 ? $parts[0] : 'Getting Started';
});
@endphp

<nav class="space-y-6">
    @foreach($groupedItems as $category => $categoryItems)
        <div class="space-y-3">
            @if($category !== 'Getting Started' || $groupedItems->count() > 1)
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 px-3">
                    {{ str_replace('-', ' ', ucfirst($category)) }}
                </h3>
            @endif

            <ul class="space-y-1">
                @foreach($categoryItems as $item)
                    @php
                        $isActive = ($active ?? '') === $item['slug'];
                        $displayTitle = $item['title'];

                        // Clean up title if it contains category prefix
                        if (str_contains($item['title'], ':')) {
                            $displayTitle = trim(explode(':', $item['title'], 2)[1] ?? $item['title']);
                        }
                    @endphp

                    <li>
                        <a
                            href="{{ url('/docs/' . $item['slug']) }}"
                            class="group flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200 @if($isActive) bg-orange-50 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400 @else text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white @endif"
                            @if($isActive) aria-current="page" @endif
                        >
                            @if($isActive)
                                <svg class="mr-2 h-2 w-2 flex-shrink-0 text-orange-500" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3"/>
                                </svg>
                            @endif

                            <span class="truncate">{{ $displayTitle }}</span>

                            @if($isActive)
                                <svg class="ml-auto h-4 w-4 flex-shrink-0 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach

    <!-- Quick Links Section -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-3">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 px-3">
            Quick Links
        </h3>

        <ul class="space-y-1">
            <li>
                <a
                    href="https://github.com/your-repo"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group flex items-center rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                >
                    <svg class="mr-2 h-4 w-4 flex-shrink-0 text-gray-400 group-hover:text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    <span>GitHub</span>
                </a>
            </li>
        </ul>
    </div>
</nav>

