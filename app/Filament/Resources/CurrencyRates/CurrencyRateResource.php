<?php

namespace App\Filament\Resources\CurrencyRates;

use App\Filament\Resources\CurrencyRates\Pages\CreateCurrencyRate;
use App\Filament\Resources\CurrencyRates\Pages\EditCurrencyRate;
use App\Filament\Resources\CurrencyRates\Pages\ListCurrencyRates;
use App\Filament\Resources\CurrencyRates\Schemas\CurrencyRateForm;
use App\Filament\Resources\CurrencyRates\Tables\CurrencyRatesTable;
use App\Models\CurrencyRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CurrencyRateResource extends Resource
{
    protected static ?string $model = CurrencyRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Exchange Rates';

    protected static ?string $modelLabel = 'Exchange Rate';

    protected static ?string $pluralModelLabel = 'Exchange Rates';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'rate';

    public static function form(Schema $schema): Schema
    {
        return CurrencyRateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CurrencyRatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurrencyRates::route('/'),
            'create' => CreateCurrencyRate::route('/create'),
            'edit' => EditCurrencyRate::route('/{record}/edit'),
        ];
    }
}
