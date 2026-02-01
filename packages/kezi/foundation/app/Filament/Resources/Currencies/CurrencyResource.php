<?php

namespace Kezi\Foundation\Filament\Resources\Currencies;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Kezi\Foundation\Filament\Resources\Currencies\Pages\CreateCurrency;
use Kezi\Foundation\Filament\Resources\Currencies\Pages\EditCurrency;
use Kezi\Foundation\Filament\Resources\Currencies\Pages\ListCurrencies;
use Kezi\Foundation\Models\Currency;

class CurrencyResource extends Resource
{
    use Translatable;

    protected static bool $isScopedToTenant = false;

    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getLabel(): ?string
    {
        return __('foundation::currency.label');
    }

    public static function getPluralLabel(): ?string
    {
        return __('foundation::currency.plural_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('foundation::navigation.groups.general_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('foundation::currency.basic_information'))
                    ->schema([
                        TextInput::make('code')
                            ->label(__('foundation::currency.code'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label(__('foundation::currency.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('symbol')
                            ->label(__('foundation::currency.symbol'))
                            ->required()
                            ->maxLength(5),
                        TextInput::make('exchange_rate')
                            ->label(__('foundation::currency.exchange_rate'))
                            ->required()
                            ->numeric(),
                        Toggle::make('is_active')
                            ->label(__('foundation::currency.is_active'))
                            ->required(),
                        DateTimePicker::make('last_updated_at')
                            ->label(__('foundation::currency.last_updated_at')),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('foundation::currency.code'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('foundation::currency.name'))
                    ->searchable(),
                TextColumn::make('symbol')
                    ->label(__('foundation::currency.symbol'))
                    ->searchable(),
                TextColumn::make('exchange_rate')
                    ->label(__('foundation::currency.exchange_rate'))
                    ->formatStateUsing(fn ($state) => \Kezi\Foundation\Support\NumberFormatter::formatNumber($state, 4))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('foundation::currency.is_active'))
                    ->boolean(),
                TextColumn::make('last_updated_at')
                    ->label(__('foundation::currency.last_updated_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('foundation::currency.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('foundation::currency.updated_at'))
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
