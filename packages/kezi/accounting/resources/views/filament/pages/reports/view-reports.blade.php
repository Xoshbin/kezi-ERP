<x-filament-panels::page>
    <style>
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.5rem;
        }
        @media (min-width: 768px) {
            .reports-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (min-width: 1280px) {
            .reports-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        .reports-grid > * {
            min-width: 0;
        }
    </style>
    <div class="reports-grid">
        @foreach($this->getReportCategories() as $categoryKey => $category)
            @foreach($category['reports'] as $report)
                <x-filament::section
                    :heading="$report['name']"
                    :description="$report['description']"
                    class="hover:shadow-lg transition-shadow duration-200"
                >
                    <x-slot name="headerEnd">
                        @if($report['icon'])
                            <x-dynamic-component
                                :component="$report['icon']"
                                class="w-5 h-5 text-gray-400"
                            />
                        @endif
                    </x-slot>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $category['title'] }}
                            </div>
                            <x-filament::badge color="gray" size="sm">
                                {{ ucfirst(str_replace('_', ' ', $categoryKey)) }}
                            </x-filament::badge>
                        </div>

                        <x-filament::button
                            :href="$report['url']"
                            color="primary"
                            size="sm"
                            class="w-full"
                            tag="a"
                        >
                            <x-slot name="icon">
                                @if($report['icon'])
                                    <x-dynamic-component :component="$report['icon']" class="w-4 h-4" />
                                @endif
                            </x-slot>
                            {{ $report['button_text'] }}
                        </x-filament::button>
                    </div>
                </x-filament::section>
            @endforeach
        @endforeach
    </div>
</x-filament-panels::page>
