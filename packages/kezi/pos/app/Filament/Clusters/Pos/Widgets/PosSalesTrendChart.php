<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Kezi\Pos\Models\PosOrder;

class PosSalesTrendChart extends ChartWidget
{
    protected ?string $heading = 'Today\'s Sales Trend';

    protected function getData(): array
    {
        /** @var \App\Models\Company|null $company */
        $company = Filament::getTenant()
            ?? auth()->user()?->companies()->first();
        $companyId = $company?->id;
        $today = Carbon::today();

        $orders = PosOrder::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('ordered_at', $today)
            ->with('currency')
            ->get(['ordered_at', 'total_amount', 'currency_id']);

        $hourlyData = array_fill(0, 24, 0);

        foreach ($orders as $order) {
            $hour = (int) $order->ordered_at->format('H');
            $hourlyData[$hour] += $order->total_amount->getAmount()->toFloat();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Sales',
                    'data' => $hourlyData,
                ],
            ],
            'labels' => array_map(fn ($h) => sprintf('%02d:00', $h), range(0, 23)),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
