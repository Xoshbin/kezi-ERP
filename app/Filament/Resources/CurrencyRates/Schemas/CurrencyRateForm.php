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
                    ->label('Currency')
                    ->relationship('currency', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Currency $record): string => "{$record->name} ({$record->code})")
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('rate')
                    ->label('Exchange Rate')
                    ->required()
                    ->numeric()
                    ->step(0.0001)
                    ->minValue(0)
                    ->helperText('Rate relative to company base currency (1 foreign currency = X base currency)'),

                DatePicker::make('effective_date')
                    ->label('Effective Date')
                    ->required()
                    ->default(Carbon::today())
                    ->maxDate(Carbon::today()),

                Select::make('source')
                    ->label('Source')
                    ->options([
                        'manual' => 'Manual Entry',
                        'api' => 'API Import',
                        'bank' => 'Bank Rate',
                        'central_bank' => 'Central Bank',
                    ])
                    ->default('manual')
                    ->required(),
            ])
            ->columns(2);
    }
}
