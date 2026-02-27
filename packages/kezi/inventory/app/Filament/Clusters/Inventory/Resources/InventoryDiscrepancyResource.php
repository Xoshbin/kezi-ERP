<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Kezi\Inventory\Filament\Clusters\Inventory\InventoryCluster;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\InventoryDiscrepancyResource\Pages;
use Kezi\Inventory\Models\StockQuant;

class InventoryDiscrepancyResource extends Resource
{
    protected static ?string $model = StockQuant::class;

    protected static ?string $cluster = InventoryCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('inventory::navigation.groups.operations');
    }

    public static function getModelLabel(): string
    {
        return __('inventory::stock_quant.discrepancy_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inventory::stock_quant.discrepancy_plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_negative_stock', true);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('inventory::stock_quant.fields.product'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('location.name')
                    ->label(__('inventory::stock_quant.fields.location'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('lot.lot_code')
                    ->label(__('inventory::stock_quant.fields.lot'))
                    ->sortable()
                    ->searchable()
                    ->placeholder(__('inventory::stock_quant.no_lot')),

                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('inventory::stock_quant.fields.quantity'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('reserved_quantity')
                    ->label(__('inventory::stock_quant.fields.reserved_quantity'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('inventory::stock_quant.fields.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->label(__('inventory::stock_quant.filters.location'))
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('reconcile')
                    ->label(__('inventory::stock_quant.actions.reconcile'))
                    ->icon('heroicon-o-check-circle')
                    ->url(fn (StockQuant $record): string => StockQuantResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('quantity', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryDiscrepancies::route('/'),
        ];
    }
}
