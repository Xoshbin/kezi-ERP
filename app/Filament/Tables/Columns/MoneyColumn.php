<?php

namespace App\Filament\Tables\Columns;

use App\Models\Currency;
use App\Support\NumberFormatter;
use Brick\Money\Money;
use Exception;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

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
                return NumberFormatter::formatMoneyTo($state);
            }

            // Handle raw numeric values (like pivot fields) - construct Money object
            if (is_numeric($state) && $record) {
                $money = $this->getMoneyObject($state, $record);
                if ($money instanceof Money) {
                    return NumberFormatter::formatMoneyTo($money);
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
    protected function getMoneyObject(mixed $state, Model $record): ?Money
    {
        if (! is_numeric($state)) {
            return null;
        }

        $currencyCode = null;

        // Strategy 1: Try to get currency directly from the record
        if (method_exists($record, 'currency')) {
            $currencyModel = $record->relationLoaded('currency') ? $record->getRelation('currency') : $record->currency()->first();
            if ($currencyModel) {
                $currencyCode = $currencyModel->code;
            }
        } elseif (isset($record->currency_id)) {
            $currency = Currency::find($record->currency_id);
            // Ensure we have a single Currency model, not a collection
            if ($currency instanceof \Illuminate\Database\Eloquent\Collection) {
                $currency = $currency->first();
            }
            if ($currency) {
                $currencyCode = $currency->code;
            }
        }

        // Strategy 2: For pivot relationships, try to get currency from the table context
        // This handles cases where we're in a relation manager and need the parent record's currency
        if (! $currencyCode) {
            $table = $this->getTable();
            $livewire = $table->getLivewire();
            if (method_exists($livewire, 'getOwnerRecord')) {
                /** @var \Illuminate\Database\Eloquent\Model|null $ownerRecord */
                $ownerRecord = $livewire->getOwnerRecord();
            } elseif (method_exists($livewire, 'getRecord')) {
                // getRecord() exists only on some resource pages
                /** @var \Illuminate\Database\Eloquent\Model|null $ownerRecord */
                $ownerRecord = $livewire->getRecord();
            } else {
                $ownerRecord = null;
            }

            if ($ownerRecord instanceof \Illuminate\Database\Eloquent\Model && method_exists($ownerRecord, 'currency')) {
                /** @var \App\Models\Currency|null $currency */
                $currency = $ownerRecord->relationLoaded('currency') ? $ownerRecord->getRelation('currency') : $ownerRecord->currency()->first();
                if ($currency) {
                    $currencyCode = $currency->code;
                }
            }
        }

        // If we have a currency, create the Money object from minor units
        if ($currencyCode) {
            try {
                return Money::ofMinor((int) $state, $currencyCode);
            } catch (Exception) {
                // If minor units fail, try as major units
                try {
                    return Money::of($state, $currencyCode);
                } catch (Exception) {
                    // Fall through to return null
                }
            }
        }

        return null;
    }
}
