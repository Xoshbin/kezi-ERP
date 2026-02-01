<?php

namespace Jmeryar\Inventory\Filament\Clusters\Inventory\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Jmeryar\Inventory\Services\Inventory\InventoryReportingService;

class InventoryValueChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getReportingService(): InventoryReportingService
    {
        return app(InventoryReportingService::class);
    }

    public function getHeading(): ?string
    {
        return __('inventory::inventory_dashboard.charts.inventory_value.title');
    }

    public function getDescription(): ?string
    {
        return __('inventory::inventory_dashboard.charts.inventory_value.description');
    }

    protected function getData(): array
    {
        $filters = $this->getFilters();
        $cacheKey = 'inventory_value_chart_'.md5(serialize($filters));

        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $dateFrom = Carbon::parse($filters['date_from'] ?? now()->subDays(30));
            $dateTo = Carbon::parse($filters['date_to'] ?? now());

            $labels = [];
            $inventoryValues = [];

            // Generate data points for the last 30 days or specified range
            $period = $dateFrom->diffInDays($dateTo);
            $interval = max(1, intval($period / 30)); // Show max 30 data points

            $currentDate = $dateFrom->copy();
            while ($currentDate->lte($dateTo)) {
                $labels[] = $currentDate->format('M j');

                $valuation = $this->getReportingService()->valuationAt($currentDate, $filters);
                $inventoryValues[] = $valuation['total_value']->getAmount()->toFloat();

                $currentDate->addDays($interval);
            }

            return [
                'datasets' => [
                    [
                        'label' => __('inventory::inventory_dashboard.charts.inventory_value.dataset_label'),
                        'data' => $inventoryValues,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true,
                        'tension' => 0.4,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return new Intl.NumberFormat().format(value); }',
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'maintainAspectRatio' => false,
        ];
    }

    protected function getFilters(): array
    {
        return $this->filters ?? [];
    }
}
