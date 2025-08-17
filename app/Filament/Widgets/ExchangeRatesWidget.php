<?php

namespace App\Filament\Widgets;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExchangeRatesWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = [];

        // Get active currencies with recent rates
        $currencies = Currency::where('is_active', true)
            ->whereHas('rates', function ($query) {
                $query->where('effective_date', '>=', Carbon::now()->subDays(7));
            })
            ->with(['latestRate'])
            ->limit(6) // Limit to 6 currencies for display
            ->get();

        foreach ($currencies as $currency) {
            $latestRate = $currency->latestRate;

            if (!$latestRate) {
                continue;
            }

            // Get previous rate for comparison
            $previousRate = CurrencyRate::where('currency_id', $currency->id)
                ->where('effective_date', '<', $latestRate->effective_date)
                ->orderBy('effective_date', 'desc')
                ->first();

            $change = null;
            $changeColor = 'gray';
            $changeIcon = null;

            if ($previousRate) {
                $changePercent = (($latestRate->rate - $previousRate->rate) / $previousRate->rate) * 100;
                $change = number_format($changePercent, 2) . '%';

                if ($changePercent > 0) {
                    $changeColor = 'success';
                    $changeIcon = 'heroicon-m-arrow-trending-up';
                } elseif ($changePercent < 0) {
                    $changeColor = 'danger';
                    $changeIcon = 'heroicon-m-arrow-trending-down';
                } else {
                    $changeIcon = 'heroicon-m-minus';
                }
            }

            $stat = Stat::make(
                $currency->code,
                number_format($latestRate->rate, 6)
            )
                ->description($currency->name)
                ->descriptionIcon('heroicon-m-currency-dollar');

            if ($change) {
                $stat = $stat->chart([
                    $previousRate->rate,
                    $latestRate->rate,
                ])
                ->color($changeColor);

                if ($changeIcon) {
                    $stat = $stat->descriptionIcon($changeIcon);
                }
            }

            $stats[] = $stat;
        }

        // If no currencies found, show a placeholder
        if (empty($stats)) {
            $stats[] = Stat::make('No Exchange Rates', 'No recent exchange rates available')
                ->description('Update exchange rates to see current data')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning');
        }

        return $stats;
    }

    protected function getColumns(): int
    {
        return 3; // Display 3 stats per row
    }
}
