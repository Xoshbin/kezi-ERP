@php
    /** @var array<int,array{level:int,id:string,text:string}> $toc */
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ckb']);
@endphp

@if (count($toc) > 0)
    <nav class="docs-toc">
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                On this page
            </h3>

            <ul class="space-y-1 text-sm">
                @foreach ($toc as $item)
                    @php
                        $isNested = $item['level'] === 3;
                        $baseClasses = 'block rounded-md px-3 py-1.5 text-sm transition-colors duration-200';
                        $defaultClasses =
                            'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white';
                        $activeClasses = 'bg-orange-50 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400';
                    @endphp

                    <li class="{{ $isNested ? ($isRtl ? 'mr-4' : 'ml-4') : '' }}">
                        <a href="#{{ $item['id'] }}" class="{{ $baseClasses }} {{ $defaultClasses }}"
                            data-toc-link="{{ $item['id'] }}">
                            @if ($isNested)
                                <span class="{{ $isRtl ? 'ml-2' : 'mr-2' }} text-gray-400">—</span>
                            @endif
                            {{ $item['text'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <!-- Scroll to top button -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <button onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
                    class="flex items-center {{ $isRtl ? 'gap-2 flex-row-reverse' : 'gap-2' }} rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 transition-colors duration-200 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                    Back to top
                </button>
            </div>
        </div>
    </nav>


@endif
