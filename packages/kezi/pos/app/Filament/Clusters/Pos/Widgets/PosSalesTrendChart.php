<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Kezi\Pos\Models\PosOrder;

class PosSalesTrendChart extends ChartWidget
{
    protected ?string $heading = 'Today\'s Sales Trend';

    protected function getData(): array
    {
        $today = Carbon::today();

        $orders = PosOrder::query()
            ->where('status', '!=', 'cancelled')
            ->whereDate('ordered_at', $today)
            ->get();

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
