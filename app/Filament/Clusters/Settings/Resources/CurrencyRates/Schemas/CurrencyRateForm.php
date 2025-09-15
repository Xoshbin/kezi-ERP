<?php

namespace App\Filament\Clusters\Settings\Resources\CurrencyRates\Schemas;

use App\Models\Currency;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CurrencyRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TranslatableSelect::make('currency_id')
                    ->relationship('currency', 'name')
                    ->label(__('currency.exchange_rates.currency'))
                    ->searchableFields(['name', 'code'])
                    ->preload()
                    ->getOptionLabelUsing(function ($record) {
                        if (!$record) return '';
                        $currencyName = is_array($record->name) ? ($record->name['en'] ?? (empty($record->name) ? '' : (string) array_values($record->name)[0])) : (string) $record->name;
                        return "{$currencyName} ({$record->code})";
                    })
                    ->required(),

                TextInput::make('rate')
                    ->label(__('currency.exchange_rates.rate'))
                    ->required()
                    ->numeric()
                    ->step(0.0001)
                    ->minValue(0)
                    ->helperText(__('currency.exchange_rates.rate_helper')),

                DatePicker::make('effective_date')
                    ->label(__('currency.exchange_rates.effective_date'))
                    ->required()
                    ->default(Carbon::today())
                    ->maxDate(Carbon::today()),

                Select::make('source')
                    ->label(__('currency.exchange_rates.source'))
                    ->options([
                        'manual' => __('currency.exchange_rates.sources.manual'),
                        'api' => __('currency.exchange_rates.sources.api'),
                        'bank' => __('currency.exchange_rates.sources.bank'),
                        'central_bank' => __('currency.exchange_rates.sources.central_bank'),
                    ])
                    ->default('manual')
                    ->required(),
            ])
            ->columns(2);
    }
}
