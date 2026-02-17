<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosSession;

class PosStatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $ordersToday = PosOrder::query()
            ->where('status', '!=', 'cancelled')
            ->whereDate('ordered_at', $today)
            ->get();

        $totalSalesToday = $ordersToday->sum(fn ($order) => $order->total_amount->getAmount()->toFloat());
        $totalOrdersToday = $ordersToday->count();

        $activeSessions = PosSession::query()
            ->where('status', 'opened')
            ->count();

        $avgTicketSize = $totalOrdersToday > 0
            ? $totalSalesToday / $totalOrdersToday
            : 0;

        return [
            Stat::make('Total Sales (Today)', number_format($totalSalesToday, 2)),
            Stat::make('Total Orders (Today)', $totalOrdersToday),
            Stat::make('Active Sessions', $activeSessions),
            Stat::make('Avg. Ticket Size (Today)', number_format($avgTicketSize, 2)),
        ];
    }
}
