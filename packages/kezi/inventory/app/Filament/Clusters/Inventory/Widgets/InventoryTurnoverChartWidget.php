<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Kezi\Inventory\Enums\Inventory\StockMoveType;

class InventoryTurnoverChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('inventory::inventory_dashboard.charts.turnover.title');
    }

    public function getDescription(): ?string
    {
        return __('inventory::inventory_dashboard.charts.turnover.description');
    }

    protected function getData(): array
    {
        $filters = $this->getFilters();
        $cacheKey = 'inventory_turnover_chart_'.md5(serialize($filters));

        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $dateFrom = Carbon::parse($filters['date_from'] ?? now()->subDays(30));
            $dateTo = Carbon::parse($filters['date_to'] ?? now());

            $labels = [];
            $receipts = [];
            $deliveries = [];

            // Generate weekly data points
            $currentDate = $dateFrom->copy()->startOfWeek();
            while ($currentDate->lte($dateTo)) {
                $weekEnd = $currentDate->copy()->endOfWeek();
                $labels[] = $currentDate->format('M j').' - '.$weekEnd->format('M j');

                // Get receipts for this week
                $weekReceipts = \Kezi\Inventory\Models\StockMoveProductLine::query()
                    ->whereHas('stockMove', function ($query) use ($currentDate, $weekEnd) {
                        $query->where('move_type', StockMoveType::Incoming)
                            ->whereBetween('move_date', [$currentDate, $weekEnd]);
                    })
                    ->when($filters['location_id'] ?? null, fn ($q, $locationId) => $q->where('to_location_id', $locationId))
                    ->when($filters['product_ids'] ?? null, fn ($q, $productIds) => $q->whereIn('product_id', $productIds))
                    ->sum('quantity');

                // Get deliveries for this week
                $weekDeliveries = \Kezi\Inventory\Models\StockMoveProductLine::query()
                    ->whereHas('stockMove', function ($query) use ($currentDate, $weekEnd) {
                        $query->where('move_type', StockMoveType::Outgoing)
                            ->whereBetween('move_date', [$currentDate, $weekEnd]);
                    })
                    ->when($filters['location_id'] ?? null, fn ($q, $locationId) => $q->where('from_location_id', $locationId))
                    ->when($filters['product_ids'] ?? null, fn ($q, $productIds) => $q->whereIn('product_id', $productIds))
                    ->sum('quantity');

                $receipts[] = (float) $weekReceipts;
                $deliveries[] = (float) $weekDeliveries;

                $currentDate->addWeek();
            }

            return [
                'datasets' => [
                    [
                        'label' => __('inventory::inventory_dashboard.charts.turnover.receipts_label'),
                        'data' => $receipts,
                        'backgroundColor' => '#10b981',
                        'borderColor' => '#10b981',
                        'borderWidth' => 2,
                    ],
                    [
                        'label' => __('inventory::inventory_dashboard.charts.turnover.deliveries_label'),
                        'data' => $deliveries,
                        'backgroundColor' => '#f59e0b',
                        'borderColor' => '#f59e0b',
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'bar';
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
