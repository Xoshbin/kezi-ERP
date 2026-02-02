<?php

namespace Kezi\Accounting\Enums\Consolidation;

use Filament\Support\Contracts\HasLabel;

enum CurrencyTranslationMethod: string implements HasLabel
{
    case ClosingRate = 'closing_rate';
    case AverageRate = 'average_rate';
    case HistoricalRate = 'historical_rate';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ClosingRate => 'Closing Rate (Balance Sheet)',
            self::AverageRate => 'Average Rate (P&L)',
            self::HistoricalRate => 'Historical Rate (Equity)',
        };
    }
}
