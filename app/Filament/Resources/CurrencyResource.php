<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Resources\CurrencyResource\Pages;
use App\Filament\Resources\CurrencyResource\RelationManagers;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Resources\Concerns\Translatable;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CurrencyResource extends Resource
{
    use Translatable;

    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Settings::class;

    public static function getLabel(): ?string
    {
        return __('currency.label');
    }

    public static function getPluralLabel(): ?string
    {
        return __('currency.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label(__('currency.code'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label(__('currency.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('symbol')
                    ->label(__('currency.symbol'))
                    ->required()
                    ->maxLength(5),
                Forms\Components\TextInput::make('exchange_rate')
                    ->label(__('currency.exchange_rate'))
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('currency.is_active'))
                    ->required(),
                Forms\Components\DateTimePicker::make('last_updated_at')
                    ->label(__('currency.last_updated_at')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('currency.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('currency.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('symbol')
                    ->label(__('currency.symbol'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('exchange_rate')
                    ->label(__('currency.exchange_rate'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('currency.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_updated_at')
                    ->label(__('currency.last_updated_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('currency.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('currency.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
