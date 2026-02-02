<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Kezi\Inventory\Services\Inventory\InventoryReportingService;

class InventoryAgingChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getReportingService(): InventoryReportingService
    {
        return app(InventoryReportingService::class);
    }

    public function getHeading(): ?string
    {
        return __('inventory::inventory_dashboard.charts.aging.title');
    }

    public function getDescription(): ?string
    {
        return __('inventory::inventory_dashboard.charts.aging.description');
    }

    protected function getData(): array
    {
        $filters = $this->getFilters();
        $cacheKey = 'inventory_aging_chart_'.md5(serialize($filters));

        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $aging = $this->getReportingService()->ageing([
                'buckets' => [
                    ['min' => 0, 'max' => 30, 'label' => __('inventory::inventory_dashboard.stats.aging_intervals.0_30')],
                    ['min' => 31, 'max' => 60, 'label' => __('inventory::inventory_dashboard.stats.aging_intervals.31_60')],
                    ['min' => 61, 'max' => 90, 'label' => __('inventory::inventory_dashboard.stats.aging_intervals.61_90')],
                    ['min' => 91, 'max' => 180, 'label' => __('inventory::inventory_dashboard.stats.aging_intervals.91_180')],
                    ['min' => 181, 'max' => 365, 'label' => __('inventory::inventory_dashboard.stats.aging_intervals.181_365')],
                    ['min' => 366, 'max' => 9999, 'label' => __('inventory::inventory_dashboard.stats.aging_intervals.365_plus')],
                ],
                ...$filters,
            ]);

            $labels = [];
            $quantities = [];
            $values = [];
            $colors = [
                '#10b981', // Green for fresh inventory
                '#f59e0b', // Amber for moderate age
                '#ef4444', // Red for old inventory
                '#8b5cf6', // Purple for very old
                '#6b7280', // Gray for ancient
                '#374151', // Dark gray for extremely old
            ];

            foreach ($aging['buckets'] as $label => $bucket) {
                $labels[] = $label;
                $quantities[] = $bucket['quantity'];
                $values[] = $bucket['value']->getAmount()->toFloat();
            }

            return [
                'datasets' => [
                    [
                        'label' => __('inventory::inventory_dashboard.charts.aging.quantity_label'),
                        'data' => $quantities,
                        'backgroundColor' => $colors,
                        'borderColor' => $colors,
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.label + ": " + new Intl.NumberFormat().format(context.parsed) + " units";
                        }',
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    protected function getFilters(): array
    {
        return $this->filters ?? [];
    }
}
