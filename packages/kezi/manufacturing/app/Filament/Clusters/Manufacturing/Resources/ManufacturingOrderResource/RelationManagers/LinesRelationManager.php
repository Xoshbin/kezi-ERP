<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Kezi\Product\Filament\Forms\Components\ProductSelectField;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('manufacturing::manufacturing.bom.components');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            ProductSelectField::make('product_id')
                ->label(__('manufacturing::manufacturing.lines.component_product'))
                ->required(),

            Forms\Components\TextInput::make('quantity_required')
                ->label(__('manufacturing::manufacturing.lines.quantity_required'))
                ->numeric()
                ->required()
                ->minValue(0.0001),

            Forms\Components\TextInput::make('quantity_consumed')
                ->label(__('manufacturing::manufacturing.lines.quantity_consumed'))
                ->numeric()
                ->default(0)
                ->disabled(),

            Forms\Components\TextInput::make('unit_cost')
                ->label(__('manufacturing::manufacturing.lines.unit_cost'))
                ->numeric()
                ->disabled(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('manufacturing::manufacturing.lines.component'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_required')
                    ->label(__('manufacturing::manufacturing.lines.qty_required'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_consumed')
                    ->label(__('manufacturing::manufacturing.lines.qty_consumed'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label(__('manufacturing::manufacturing.lines.unit_cost'))
                    ->money(fn ($record) => $record->currency_code ?? 'USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label(__('manufacturing::manufacturing.lines.total_cost'))
                    ->money(fn ($record) => $record->currency_code ?? 'USD')
                    ->getStateUsing(fn ($record) => $record->quantity_consumed * ($record->unit_cost ?? 0))
                    ->sortable(),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->status->value === 'draft'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->status->value === 'draft'),
                \Filament\Actions\DeleteAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->status->value === 'draft'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->visible(fn () => $this->getOwnerRecord()->status->value === 'draft'),
                ]),
            ]);
    }
}
