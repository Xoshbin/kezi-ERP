<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Stats Widget - Full Width --}}
        @livewire(\Jmeryar\Inventory\Filament\Clusters\Inventory\Widgets\InventoryStatsOverviewWidget::class)

        {{-- Charts Grid - 2 Column Layout --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Inventory Value Chart - Spans both columns on large screens --}}
            <div class="lg:col-span-2">
                @livewire(\Jmeryar\Inventory\Filament\Clusters\Inventory\Widgets\InventoryValueChartWidget::class)
            </div>

            {{-- Turnover and Aging Charts - Side by Side --}}
            <div>
                @livewire(\Jmeryar\Inventory\Filament\Clusters\Inventory\Widgets\InventoryTurnoverChartWidget::class)
            </div>
            <div>
                @livewire(\Jmeryar\Inventory\Filament\Clusters\Inventory\Widgets\InventoryAgingChartWidget::class)
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-plus class="h-8 w-8 text-primary-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ __('inventory::inventory_dashboard.quick_actions.new_receipt.title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('inventory::inventory_dashboard.quick_actions.new_receipt.description') }}
                        </p>
                        <x-filament::button
                            tag="a"
                            href="{{ route('filament.jmeryar.inventory.resources.stock-moves.create', ['tenant' => filament()->getTenant(), 'type' => 'incoming']) }}"
                            size="sm"
                            class="mt-2"
                        >
                            {{ __('inventory::inventory_dashboard.quick_actions.new_receipt.button') }}
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-minus class="h-8 w-8 text-warning-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ __('inventory::inventory_dashboard.quick_actions.new_delivery.title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('inventory::inventory_dashboard.quick_actions.new_delivery.description') }}
                        </p>
                        <x-filament::button
                            tag="a"
                            href="{{ route('filament.jmeryar.inventory.resources.stock-moves.create', ['tenant' => filament()->getTenant(), 'type' => 'outgoing']) }}"
                            size="sm"
                            class="mt-2"
                            color="warning"
                        >
                            {{ __('inventory::inventory_dashboard.quick_actions.new_delivery.button') }}
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-chart-bar class="h-8 w-8 text-success-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ __('inventory::inventory_dashboard.quick_actions.reports.title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('inventory::inventory_dashboard.quick_actions.reports.description') }}
                        </p>
                        <x-filament::button
                            tag="a"
                            href="{{ route('filament.jmeryar.inventory.pages.inventory-valuation-report', ['tenant' => filament()->getTenant()]) }}"
                            size="sm"
                            class="mt-2"
                            color="success"
                        >
                            {{ __('inventory::inventory_dashboard.quick_actions.reports.button') }}
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::card>
        </div>
    </div>
</x-filament-panels::page>
