<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Widgets;

use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\CurrencyRate;
use Jmeryar\Foundation\Support\TranslatableHelper;

class ExchangeRatesWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

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
            /** @var CurrencyRate|null $latestRate */
            $latestRate = $currency->latestRate;

            if (! $latestRate instanceof CurrencyRate) {
                continue;
            }

            // Get previous rate for comparison
            /** @var CurrencyRate|null $previousRate */
            $previousRate = CurrencyRate::where('currency_id', $currency->getKey())
                ->where('effective_date', '<', $latestRate->effective_date)
                ->orderBy('effective_date', 'desc')
                ->first();

            $change = null;
            $changeColor = 'gray';
            $changeIcon = null;

            if ($previousRate instanceof CurrencyRate) {
                $changePercent = (((float) $latestRate->rate - (float) $previousRate->rate) / max((float) $previousRate->rate, 0.000001)) * 100;
                $change = number_format($changePercent, 2).'%';

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

            $currencyName = TranslatableHelper::getLocalizedValue($currency->name);
            $stat = Stat::make(
                $currency->code,
                number_format((float) $latestRate->rate, 6)
            )
                ->description($currencyName)
                ->descriptionIcon('heroicon-m-currency-dollar');

            if ($change && $previousRate) {
                $stat = $stat->chart([
                    (float) $previousRate->rate,
                    (float) $latestRate->rate,
                ])
                    ->color($changeColor)
                    ->descriptionIcon($changeIcon);
            }

            $stats[] = $stat;
        }

        // If no currencies found, show a placeholder
        if (empty($stats)) {
            $stats[] = Stat::make(__('accounting::dashboard.exchange_rates.no_rates'), __('accounting::dashboard.exchange_rates.no_recent'))
                ->description(__('accounting::dashboard.exchange_rates.update_description'))
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
