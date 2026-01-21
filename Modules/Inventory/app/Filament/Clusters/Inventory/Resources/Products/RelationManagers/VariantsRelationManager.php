<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Foundation\Filament\Tables\Columns\MoneyColumn;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $recordTitleAttribute = 'sku';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->required()
                    ->maxLength(255),
                TextInput::make('variant_sku_suffix')
                    ->disabled(),
                \Modules\Foundation\Filament\Forms\Components\MoneyInput::make('unit_price')
                    ->label(__('product.unit_price'))
                    ->currencyField('currency_id')
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('variant_sku_suffix')
                    ->label(__('product::product.attributes'))
                    ->searchable(),
                MoneyColumn::make('unit_price')
                    ->label(__('product::product.price')),
                Tables\Columns\TextColumn::make('quantity_on_hand')
                    ->label(__('product::product.on_hand'))
                    ->numeric(2),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
