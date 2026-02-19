<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosSession;

class PosStatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var \App\Models\Company|null $company */
        $company = Filament::getTenant()
            ?? auth()->user()?->companies()->first();
        $companyId = $company?->id;
        $today = Carbon::today();

        // Eager-load currency so DocumentCurrencyMoneyCast can resolve the Money object.
        $ordersToday = PosOrder::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('ordered_at', $today)
            ->with('currency')
            ->get(['id', 'total_amount', 'currency_id', 'status', 'ordered_at']);

        $totalSalesToday = $ordersToday->sum(
            fn (PosOrder $order) => $order->total_amount->getAmount()->toFloat()
        );
        $totalOrdersToday = $ordersToday->count();

        $activeSessions = PosSession::query()
            ->where('company_id', $companyId)
            ->where('status', 'opened')
            ->count();

        $avgTicketSize = $totalOrdersToday > 0 ? $totalSalesToday / $totalOrdersToday : 0;

        return [
            Stat::make('Total Sales (Today)', number_format($totalSalesToday, 2)),
            Stat::make('Total Orders (Today)', $totalOrdersToday),
            Stat::make('Active Sessions', $activeSessions),
            Stat::make('Avg. Ticket Size (Today)', number_format($avgTicketSize, 2)),
        ];
    }
}
