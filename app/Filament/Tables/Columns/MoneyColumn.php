<?php

namespace App\Filament\Tables\Columns;

use Brick\Money\Money;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use NumberFormatter;
use App\Models\Currency;

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

            // If it's already a Money object, format it directly
            if ($state instanceof Money) {
                $formatter = new NumberFormatter('EN_us', NumberFormatter::CURRENCY);
                return $formatter->formatCurrency(
                    $state->getAmount()->toFloat(),
                    $state->getCurrency()->getCurrencyCode()
                );
            }

            // Handle raw numeric values (like pivot fields) - construct Money object
            if (is_numeric($state) && $record) {
                $money = $this->getMoneyObject($state, $record);
                if ($money instanceof Money) {
                    $formatter = new NumberFormatter('EN_us', NumberFormatter::CURRENCY);
                    return $formatter->formatCurrency(
                        $money->getAmount()->toFloat(),
                        $money->getCurrency()->getCurrencyCode()
                    );
                }
            }

            // Return raw value as fallback
            return $state;
        });
    }

    /**
     * Get or create a Money object from raw state and record context.
     * Similar to MoneyInput's getMoneyObject method.
     */
    protected function getMoneyObject($state, Model $record): ?Money
    {
        if (!is_numeric($state)) {
            return null;
        }

        $currencyCode = null;

        // Strategy 1: Try to get currency directly from the record
        if (method_exists($record, 'currency') && $record->currency) {
            $currencyCode = $record->currency->code;
        } elseif (isset($record->currency_id)) {
            $currency = Currency::find($record->currency_id);
            if ($currency) {
                $currencyCode = $currency->code;
            }
        }

        // Strategy 2: For pivot relationships, try to get currency from the table context
        // This handles cases where we're in a relation manager and need the parent record's currency
        if (!$currencyCode && $this->getTable()) {
            $livewire = $this->getTable()->getLivewire();
            if (method_exists($livewire, 'getOwnerRecord')) {
                $ownerRecord = $livewire->getOwnerRecord();
                if ($ownerRecord && method_exists($ownerRecord, 'currency') && $ownerRecord->currency) {
                    $currencyCode = $ownerRecord->currency->code;
                }
            }
        }

        // If we have a currency, create the Money object from minor units
        if ($currencyCode) {
            try {
                return Money::ofMinor((int)$state, $currencyCode);
            } catch (\Exception $e) {
                // If minor units fail, try as major units
                try {
                    return Money::of($state, $currencyCode);
                } catch (\Exception $e) {
                    // Fall through to return null
                }
            }
        }

        return null;
    }
}
