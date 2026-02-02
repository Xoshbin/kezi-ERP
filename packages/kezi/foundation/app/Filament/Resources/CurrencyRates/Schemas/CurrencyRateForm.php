<?php

namespace Kezi\Foundation\Filament\Resources\CurrencyRates\Schemas;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Support\TranslatableHelper;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class CurrencyRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Hidden::make('company_id')
                    ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
                TranslatableSelect::forModel('currency_id', Currency::class)
                    ->label(__('foundation::currency.exchange_rates.currency'))
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        if (! $record) {
                            return '';
                        }
                        $currencyName = TranslatableHelper::getLocalizedValue($record->name);

                        return "{$currencyName} ({$record->code})";
                    })
                    ->required(),

                TextInput::make('rate')
                    ->label(__('foundation::currency.exchange_rates.rate'))
                    ->required()
                    ->numeric()
                    ->step(0.0001)
                    ->minValue(0)
                    ->helperText(__('foundation::currency.exchange_rates.rate_helper')),

                DatePicker::make('effective_date')
                    ->label(__('foundation::currency.exchange_rates.effective_date'))
                    ->required()
                    ->default(Carbon::today())
                    ->maxDate(Carbon::today()),

                Select::make('source')
                    ->label(__('foundation::currency.exchange_rates.source'))
                    ->options([
                        'manual' => __('foundation::currency.exchange_rates.sources.manual'),
                        'api' => __('foundation::currency.exchange_rates.sources.api'),
                        'bank' => __('foundation::currency.exchange_rates.sources.bank'),
                        'central_bank' => __('foundation::currency.exchange_rates.sources.central_bank'),
                    ])
                    ->default('manual')
                    ->required(),
            ])
            ->columns(2);
    }
}
