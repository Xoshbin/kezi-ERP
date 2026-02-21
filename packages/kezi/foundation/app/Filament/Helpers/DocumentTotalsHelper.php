<?php

namespace Kezi\Foundation\Filament\Helpers;

use App\Models\Company;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Kezi\Foundation\Models\Currency;

/**
 * Helper class for creating standardized document totals sections in Filament forms.
 */
class DocumentTotalsHelper
{
    /**
     * Create a standard document totals section for Filament infolists.
     */
    public static function makeInfolist(
        string $translationPrefix = 'accounting::bill',
        ?string $subtotalLabel = null,
        ?string $taxLabel = null,
        ?string $totalLabel = null,
        ?string $companyCurrencyTotalLabel = null,
        ?string $totalsLabel = null,
        ?string $documentCurrencyLabel = null,
        ?string $companyCurrencyLabel = null,
        string $subtotalKey = 'subtotal',
        string $taxKey = 'total_tax',
        string $totalKey = 'total_amount',
        string $subtotalCompanyKey = 'subtotal_company_currency',
        string $taxCompanyKey = 'total_tax_company_currency',
        string $totalCompanyKey = 'total_amount_company_currency',
        string $exchangeRateKey = 'exchange_rate_at_creation',
    ): Section {
        return Section::make($totalsLabel ?? __("{$translationPrefix}.totals"))
            ->schema([
                Fieldset::make($documentCurrencyLabel ?? __("{$translationPrefix}.document_currency"))
                    ->schema([
                        TextEntry::make($subtotalKey)
                            ->label($subtotalLabel ?? __("{$translationPrefix}.subtotal"))
                            ->formatStateUsing(fn ($state, $record) => static::formatMoneyState($state, $record, false)),
                        TextEntry::make($taxKey)
                            ->label($taxLabel ?? __("{$translationPrefix}.tax"))
                            ->formatStateUsing(fn ($state, $record) => static::formatMoneyState($state, $record, false)),
                        TextEntry::make($totalKey)
                            ->label($totalLabel ?? __("{$translationPrefix}.total"))
                            ->formatStateUsing(fn ($state, $record) => static::formatMoneyState($state, $record, false))
                            ->weight('bold')
                            ->size('lg'),
                    ])
                    ->columns(3),

                Fieldset::make($companyCurrencyLabel ?? __("{$translationPrefix}.company_currency"))
                    ->schema([
                        TextEntry::make($subtotalCompanyKey)
                            ->label($subtotalLabel ?? __("{$translationPrefix}.subtotal"))
                            ->formatStateUsing(fn ($state, $record) => static::formatMoneyState($state, $record, true)),
                        TextEntry::make($taxCompanyKey)
                            ->label($taxLabel ?? __("{$translationPrefix}.tax"))
                            ->formatStateUsing(fn ($state, $record) => static::formatMoneyState($state, $record, true)),
                        TextEntry::make($totalCompanyKey)
                            ->label($companyCurrencyTotalLabel ?? __("{$translationPrefix}.total_amount_company_currency"))
                            ->formatStateUsing(fn ($state, $record) => static::formatMoneyState($state, $record, true))
                            ->weight('bold')
                            ->size('lg'),
                    ])
                    ->columns(3)
                    ->visible(function ($record) use ($exchangeRateKey) {
                        return $record && $record->{$exchangeRateKey} && $record->currency_id != $record->company->currency_id;
                    }),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    /**
     * Create a standard document totals section for Filament forms.
     */
    public static function make(
        string $linesKey = 'lines',
        string $translationPrefix = 'accounting::bill',
        ?string $subtotalLabel = null,
        ?string $taxLabel = null,
        ?string $totalLabel = null,
        ?string $companyCurrencyTotalLabel = null,
        ?string $taxModelClass = 'Kezi\Accounting\Models\Tax',
        ?string $totalsLabel = null,
        ?string $documentCurrencyLabel = null,
        ?string $companyCurrencyLabel = null,
        string $exchangeRateKey = 'exchange_rate_at_creation',
    ): Section {
        return Section::make($totalsLabel ?? __("{$translationPrefix}.totals"))
            ->schema([
                Fieldset::make($documentCurrencyLabel ?? __("{$translationPrefix}.document_currency"))
                    ->schema([
                        Placeholder::make('subtotal_display')
                            ->label($subtotalLabel ?? __("{$translationPrefix}.subtotal"))
                            ->content(fn (Get $get) => static::calculateTotalDisplay($get, 'subtotal', false, $linesKey, $taxModelClass, $exchangeRateKey)),
                        Placeholder::make('total_tax_display')
                            ->label($taxLabel ?? __("{$translationPrefix}.tax"))
                            ->content(fn (Get $get) => static::calculateTotalDisplay($get, 'tax', false, $linesKey, $taxModelClass, $exchangeRateKey)),
                        Placeholder::make('total_amount_display')
                            ->label($totalLabel ?? __("{$translationPrefix}.total"))
                            ->content(fn (Get $get) => static::calculateTotalDisplay($get, 'total', false, $linesKey, $taxModelClass, $exchangeRateKey))
                            ->weight('bold')
                            ->size('lg'),
                    ])
                    ->columns(3),

                Fieldset::make($companyCurrencyLabel ?? __("{$translationPrefix}.company_currency"))
                    ->schema([
                        Placeholder::make('subtotal_company_currency_display')
                            ->label($subtotalLabel ?? __("{$translationPrefix}.subtotal"))
                            ->content(fn (Get $get) => static::calculateTotalDisplay($get, 'subtotal', true, $linesKey, $taxModelClass, $exchangeRateKey)),
                        Placeholder::make('total_tax_company_currency_display')
                            ->label($taxLabel ?? __("{$translationPrefix}.tax"))
                            ->content(fn (Get $get) => static::calculateTotalDisplay($get, 'tax', true, $linesKey, $taxModelClass, $exchangeRateKey)),
                        Placeholder::make('total_amount_company_currency_display')
                            ->label($companyCurrencyTotalLabel ?? __("{$translationPrefix}.total_amount_company_currency"))
                            ->content(fn (Get $get) => static::calculateTotalDisplay($get, 'total', true, $linesKey, $taxModelClass, $exchangeRateKey))
                            ->weight('bold')
                            ->size('lg'),
                    ])
                    ->columns(3)
                    ->visible(function (Get $get) {
                        /** @var Company|null $company */
                        $company = Filament::getTenant();

                        return $company && $get('currency_id') && $get('currency_id') != $company->currency_id;
                    }),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    /**
     * Format a money state properly to handle Brick\Money\Money.
     */
    public static function formatMoneyState(mixed $state, mixed $record, bool $isCompanyCurrency = false): string
    {
        $currency = $isCompanyCurrency ? $record?->company?->currency : $record?->currency;
        $decimalPlaces = $currency ? $currency->decimal_places : 2;
        $symbol = $currency ? $currency->symbol.' ' : '';

        $amount = 0.0;
        if ($state instanceof \Brick\Money\Money) {
            $amount = $state->getAmount()->toFloat();
        } elseif ($state instanceof \Brick\Math\BigDecimal) {
            $amount = $state->toFloat();
        } elseif (is_numeric($state)) {
            $amount = (float) $state;
        } elseif (is_string($state)) {
            $amount = (float) preg_replace('/[^0-9.-]/', '', $state);
        }

        return $symbol.number_format($amount, $decimalPlaces);
    }

    /**
     * Calculate document totals for display in Filament placeholders.
     */
    public static function calculateTotalDisplay(
        Get $get,
        string $type,
        bool $inCompanyCurrency = false,
        string $linesKey = 'lines',
        ?string $taxModelClass = 'Kezi\Accounting\Models\Tax',
        string $exchangeRateKey = 'exchange_rate_at_creation',
    ): string {
        try {
            /** @var array<int|string, array<string, mixed>>|null $linesData */
            $linesData = $get($linesKey);
            $lines = is_array($linesData) ? $linesData : [];

            if (count($lines) === 0) {
                return '-';
            }

            $currencyId = $get('currency_id');
            /** @var Currency|null $currency */
            $currency = $currencyId ? Currency::find($currencyId) : null;
            if (! $currency) {
                return '-';
            }

            $subtotal = BigDecimal::zero();
            $totalTax = BigDecimal::zero();

            foreach ($lines as $line) {
                $qty = BigDecimal::of(filled($line['quantity'] ?? null) ? $line['quantity'] : 0);
                $price = BigDecimal::of(filled($line['unit_price'] ?? null) ? $line['unit_price'] : 0);
                $taxId = $line['tax_id'] ?? null;

                if ($qty->isZero() || $price->isZero()) {
                    continue;
                }

                $lineSubtotal = $qty->multipliedBy($price);
                $subtotal = $subtotal->plus($lineSubtotal);

                if ($taxId && $taxModelClass && class_exists($taxModelClass)) {
                    $tax = $taxModelClass::find($taxId);
                    if ($tax && isset($tax->rate)) {
                        $lineTax = $lineSubtotal->multipliedBy($tax->rate)->dividedBy(100, 10, RoundingMode::HALF_UP);
                        $totalTax = $totalTax->plus($lineTax);
                    }
                }
            }

            $amount = match ($type) {
                'subtotal' => $subtotal,
                'tax' => $totalTax,
                'total' => $subtotal->plus($totalTax),
                default => BigDecimal::zero(),
            };

            if ($inCompanyCurrency) {
                $exchangeRate = (float) ($get($exchangeRateKey) ?? 1.0);
                /** @var Company|null $company */
                $company = Filament::getTenant();
                /** @var Currency|null $companyCurrency */
                $companyCurrency = $company ? Currency::find($company->currency_id) : null;

                if (! $companyCurrency) {
                    return '-';
                }

                $amountFloat = (float) (string) $amount;
                $totalInLocal = $amountFloat * $exchangeRate;

                return $companyCurrency->symbol.' '.number_format($totalInLocal, $companyCurrency->decimal_places);
            }

            return $currency->symbol.' '.number_format((float) (string) $amount, $currency->decimal_places);
        } catch (\Exception $e) {
            return '-';
        }
    }
}
