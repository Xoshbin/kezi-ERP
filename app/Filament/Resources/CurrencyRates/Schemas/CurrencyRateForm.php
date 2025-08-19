<?php

namespace App\Filament\Resources\CurrencyRates\Schemas;

use App\Models\Currency;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CurrencyRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('currency_id')
                    ->label(__('currency.exchange_rates.currency'))
                    ->relationship('currency', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Currency $record): string => "{$record->name} ({$record->code})")
                    ->searchable()
                    ->preload()
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
