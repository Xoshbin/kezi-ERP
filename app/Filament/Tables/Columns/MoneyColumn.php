<?php

namespace App\Filament\Tables\Columns;

use Brick\Money\Money;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use NumberFormatter;

class MoneyColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->numeric();

        $this->formatStateUsing(function ($state, ?Model $record): ?string {
            if ($state === null) {
                return null;
            }

            if (!$state instanceof Money) {
                return $state;
            }

            $locale = app()->getLocale();
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            
            return $formatter->formatCurrency(
                $state->getAmount()->toFloat(),
                $state->getCurrency()->getCurrencyCode()
            );
        });
    }
}