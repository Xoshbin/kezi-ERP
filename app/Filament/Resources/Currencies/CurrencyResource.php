<?php

namespace App\Filament\Resources\Currencies;

use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Currencies\Pages\ListCurrencies;
use App\Filament\Resources\Currencies\Pages\CreateCurrency;
use App\Filament\Resources\Currencies\Pages\EditCurrency;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\CurrencyResource\Pages;
use App\Filament\Resources\CurrencyResource\RelationManagers;
use App\Models\Currency;
use App\Support\NumberFormatter;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CurrencyResource extends Resource
{
    use Translatable;

    protected static ?string $model = Currency::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getLabel(): ?string
    {
        return __('currency.label');
    }

    public static function getPluralLabel(): ?string
    {
        return __('currency.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('currency.code'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('name')
                    ->label(__('currency.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('symbol')
                    ->label(__('currency.symbol'))
                    ->required()
                    ->maxLength(5),
                TextInput::make('exchange_rate')
                    ->label(__('currency.exchange_rate'))
                    ->required()
                    ->numeric(),
                Toggle::make('is_active')
                    ->label(__('currency.is_active'))
                    ->required(),
                DateTimePicker::make('last_updated_at')
                    ->label(__('currency.last_updated_at')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('currency.code'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('currency.name'))
                    ->searchable(),
                TextColumn::make('symbol')
                    ->label(__('currency.symbol'))
                    ->searchable(),
                TextColumn::make('exchange_rate')
                    ->label(__('currency.exchange_rate'))
                    ->formatStateUsing(fn ($state) => NumberFormatter::formatNumber($state, 4))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('currency.is_active'))
                    ->boolean(),
                TextColumn::make('last_updated_at')
                    ->label(__('currency.last_updated_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('currency.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('currency.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'edit' => EditCurrency::route('/{record}/edit'),
        ];
    }
}
