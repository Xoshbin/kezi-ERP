<?php

namespace Modules\Foundation\Filament\Clusters\Settings\Resources\CurrencyRates;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\Foundation\Models\CurrencyRate;

class CurrencyRateResource extends Resource
{
    use Translatable;

    protected static ?string $model = CurrencyRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'rate';

    public static function getLabel(): ?string
    {
        return __('currency.exchange_rates.label');
    }

    public static function getPluralLabel(): ?string
    {
        return __('currency.exchange_rates.plural_label');
    }

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
