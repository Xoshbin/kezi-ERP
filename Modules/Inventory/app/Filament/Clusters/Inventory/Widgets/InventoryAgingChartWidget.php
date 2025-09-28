<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Widgets;


use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Modules\Inventory\Services\Inventory\InventoryReportingService;

class InventoryAgingChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected function getReportingService(): InventoryReportingService
    {
        return app(InventoryReportingService::class);
    }

    public function getHeading(): ?string
    {
        return __('inventory_dashboard.charts.aging.title');
    }

    public function getDescription(): ?string
    {
        return __('inventory_dashboard.charts.aging.description');
    }

    protected function getData(): array
    {
        $filters = $this->getFilters();
        $cacheKey = 'inventory_aging_chart_' . md5(serialize($filters));

        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $aging = $this->getReportingService()->ageing([
                'buckets' => [
                    ['min' => 0, 'max' => 30, 'label' => '0-30 days'],
                    ['min' => 31, 'max' => 60, 'label' => '31-60 days'],
                    ['min' => 61, 'max' => 90, 'label' => '61-90 days'],
                    ['min' => 91, 'max' => 180, 'label' => '91-180 days'],
                    ['min' => 181, 'max' => 365, 'label' => '181-365 days'],
                    ['min' => 366, 'max' => 9999, 'label' => '365+ days'],
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
                        'label' => __('inventory_dashboard.charts.aging.quantity_label'),
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
