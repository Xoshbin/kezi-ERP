<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;

class ManufacturingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $companyId = auth()->user()->currentCompany->id;

        $totalOrders = ManufacturingOrder::where('company_id', $companyId)->count();

        $pendingOrders = ManufacturingOrder::where('company_id', $companyId)
            ->whereIn('status', [ManufacturingOrderStatus::Draft, ManufacturingOrderStatus::Confirmed])
            ->count();

        $inProgressOrders = ManufacturingOrder::where('company_id', $companyId)
            ->where('status', ManufacturingOrderStatus::InProgress)
            ->count();

        $completedOrders = ManufacturingOrder::where('company_id', $companyId)
            ->where('status', ManufacturingOrderStatus::Done)
            ->count();

        $completionRate = $totalOrders > 0
            ? round(($completedOrders / $totalOrders) * 100, 1)
            : 0;

        return [
            Stat::make(__('manufacturing::manufacturing.widgets.stats.pending_orders'), $pendingOrders)
                ->description(__('manufacturing::manufacturing.widgets.stats.draft_confirmed'))
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->chart($this->getPendingTrend()),

            Stat::make(__('manufacturing::manufacturing.widgets.stats.in_production'), $inProgressOrders)
                ->description(__('manufacturing::manufacturing.widgets.stats.currently_manufacturing'))
                ->descriptionIcon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->chart($this->getInProgressTrend()),

            Stat::make(__('manufacturing::manufacturing.widgets.stats.completed'), $completedOrders)
                ->description(__('manufacturing::manufacturing.widgets.stats.completion_rate', ['rate' => $completionRate]))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart($this->getCompletedTrend()),
        ];
    }

    protected function getPendingTrend(): array
    {
        $companyId = auth()->user()->currentCompany->id;

        return ManufacturingOrder::where('company_id', $companyId)
            ->whereIn('status', [ManufacturingOrderStatus::Draft, ManufacturingOrderStatus::Confirmed])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    protected function getInProgressTrend(): array
    {
        $companyId = auth()->user()->currentCompany->id;

        return ManufacturingOrder::where('company_id', $companyId)
            ->where('status', ManufacturingOrderStatus::InProgress)
            ->selectRaw('DATE(actual_start_date) as date, COUNT(*) as count')
            ->where('actual_start_date', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    protected function getCompletedTrend(): array
    {
        $companyId = auth()->user()->currentCompany->id;

        return ManufacturingOrder::where('company_id', $companyId)
            ->where('status', ManufacturingOrderStatus::Done)
            ->selectRaw('DATE(actual_end_date) as date, COUNT(*) as count')
            ->where('actual_end_date', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }
}
