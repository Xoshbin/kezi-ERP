<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Widgets;

use Carbon\Carbon;
use Brick\Money\Money;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Modules\Inventory\Services\Inventory\InventoryReportingService;

class InventoryStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getReportingService(): InventoryReportingService
    {
        return app(InventoryReportingService::class);
    }

    protected function getStats(): array
    {
        $filters = $this->getFilters();
        $cacheKey = 'inventory_stats_' . md5(serialize($filters));

        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $asOfDate = Carbon::parse($filters['date_to'] ?? now());

            $reportingService = $this->getReportingService();

            // Get inventory valuation
            $valuation = $reportingService->valuationAt($asOfDate, $filters);
            $totalValue = $valuation['total_value'] ?? Money::of(0, 'IQD');

            // Get turnover data
            $turnover = $reportingService->turnover($filters);
            $turnoverRatio = $turnover['turnover_ratio'] ?? 0;

            // Get reorder status
            $reorderStatus = $reportingService->reorderStatus($filters);
            $lowStockCount = count($reorderStatus['below_minimum'] ?? []);

            // Get aging data for expiring lots
            $aging = $reportingService->ageing([
                'include_expiration' => true,
                'expiration_warning_days' => 30,
                ...$filters,
            ]);
            $expiringLotsCount = count($aging['expiring_soon'] ?? []);

            return [
                $this->createTotalValueStat($totalValue),
                $this->createTurnoverStat($turnoverRatio),
                $this->createLowStockStat($lowStockCount),
                $this->createExpiringLotsStat($expiringLotsCount),
            ];
        });
    }

    private function createTotalValueStat(Money $totalValue): Stat
    {
        return Stat::make(__('inventory::inventory_dashboard.stats.total_value'), $totalValue->formatTo('en_US'))
            ->description(__('inventory::inventory_dashboard.stats.total_value_description'))
            ->descriptionIcon('heroicon-m-currency-dollar')
            ->color('success')
            ->chart($this->getValueTrendChart());
    }

    private function createTurnoverStat(float $turnoverRatio): Stat
    {
        $color = match (true) {
            $turnoverRatio >= 6 => 'success',
            $turnoverRatio >= 3 => 'warning',
            default => 'danger'
        };

        $icon = match (true) {
            $turnoverRatio >= 6 => 'heroicon-m-arrow-trending-up',
            $turnoverRatio >= 3 => 'heroicon-m-arrow-right',
            default => 'heroicon-m-arrow-trending-down'
        };

        return Stat::make(__('inventory::inventory_dashboard.stats.turnover_ratio'), number_format($turnoverRatio, 2) . 'x')
            ->description(__('inventory::inventory_dashboard.stats.turnover_description'))
            ->descriptionIcon($icon)
            ->color($color);
    }

    private function createLowStockStat(int $lowStockCount): Stat
    {
        $color = $lowStockCount > 0 ? 'danger' : 'success';
        $icon = $lowStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle';

        return Stat::make(__('inventory::inventory_dashboard.stats.low_stock'), $lowStockCount)
            ->description(__('inventory::inventory_dashboard.stats.low_stock_description'))
            ->descriptionIcon($icon)
            ->color($color);
    }

    private function createExpiringLotsStat(int $expiringLotsCount): Stat
    {
        $color = $expiringLotsCount > 0 ? 'warning' : 'success';
        $icon = $expiringLotsCount > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle';

        return Stat::make(__('inventory::inventory_dashboard.stats.expiring_lots'), $expiringLotsCount)
            ->description(__('inventory::inventory_dashboard.stats.expiring_lots_description'))
            ->descriptionIcon($icon)
            ->color($color);
    }

    private function getValueTrendChart(): array
    {
        // Simple 7-day trend chart
        $data = [];
        $reportingService = $this->getReportingService();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $valuation = $reportingService->valuationAt($date, $this->getFilters());
            $data[] = $valuation['total_value']->getAmount()->toFloat();
        }
        return $data;
    }

    protected function getFilters(): array
    {
        return $this->filters ?? [];
    }
}
