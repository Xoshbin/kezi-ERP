<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosReturn;

class PosReturnStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var \App\Models\Company|null $company */
        $company = Filament::getTenant();
        $companyId = $company?->id;
        $today = Carbon::today();

        $pendingApprovals = PosReturn::query()
            ->where('company_id', $companyId)
            ->where('status', PosReturnStatus::PendingApproval)
            ->count();

        $returnsToday = PosReturn::query()
            ->where('company_id', $companyId)
            ->whereIn('status', [PosReturnStatus::Approved, PosReturnStatus::Processing, PosReturnStatus::Completed])
            ->whereDate('return_date', $today)
            ->with('currency')
            ->get();

        $refundsToday = $returnsToday->sum(
            fn (PosReturn $return) => $return->refund_amount->getAmount()->toFloat()
        );

        // Return rate: (Returns count today / Total Orders count today)
        $totalOrdersToday = PosOrder::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('ordered_at', $today)
            ->count();

        $totalReturnsToday = $returnsToday->count();

        $returnRate = $totalOrdersToday > 0 ? ($totalReturnsToday / $totalOrdersToday) * 100 : 0;

        return [
            Stat::make(__('pos::pos_return.pending_approvals'), $pendingApprovals)
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make(__('pos::pos_return.refunds_today'), number_format($refundsToday, 2))
                ->color('danger')
                ->icon('heroicon-o-arrow-path'),
            Stat::make(__('pos::pos_return.return_rate'), number_format($returnRate, 1).'%')
                ->color($returnRate > 10 ? 'danger' : 'success')
                ->icon('heroicon-o-presentation-chart-line'),
        ];
    }
}
