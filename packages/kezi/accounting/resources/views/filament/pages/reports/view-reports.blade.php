<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($this->getReportCategories() as $categoryKey => $category)
            @foreach($category['reports'] as $report)
                <x-filament::section
                    :heading="$report['name']"
                    :description="$report['description']"
                    class="hover:shadow-md transition-shadow duration-200"
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
