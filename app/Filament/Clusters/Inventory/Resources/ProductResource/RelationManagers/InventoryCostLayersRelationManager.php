<?php

namespace App\Filament\Clusters\Inventory\Resources\ProductResource\RelationManagers;

use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\InventoryCostLayer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryCostLayersRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryCostLayers';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('product.inventory_cost_layers');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('info')
                    ->label(__('inventory_cost_layer.info'))
                    ->content(__('inventory_cost_layer.info_description'))
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label(__('inventory_cost_layer.purchase_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('inventory_cost_layer.quantity'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_quantity')
                    ->label(__('inventory_cost_layer.remaining_quantity'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable()
                    ->color(fn (float $state): string => $state > 0 ? 'success' : 'gray'),
                MoneyColumn::make('cost_per_unit')
                    ->label(__('inventory_cost_layer.cost_per_unit'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label(__('inventory_cost_layer.total_cost'))
                    ->formatStateUsing(function (InventoryCostLayer $record): string {
                        $totalCost = $record->cost_per_unit->multipliedBy($record->quantity);
                        return $totalCost->formatTo('en_US');
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_value')
                    ->label(__('inventory_cost_layer.remaining_value'))
                    ->formatStateUsing(function (InventoryCostLayer $record): string {
                        $remainingValue = $record->cost_per_unit->multipliedBy($record->remaining_quantity);
                        return $remainingValue->formatTo('en_US');
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('source_type')
                    ->label(__('inventory_cost_layer.source'))
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('inventory_cost_layer.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('purchase_date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('has_remaining_quantity')
                    ->label(__('inventory_cost_layer.has_remaining_quantity'))
                    ->query(fn (Builder $query): Builder => $query->where('remaining_quantity', '>', 0))
                    ->toggle(),
                Tables\Filters\Filter::make('fully_consumed')
                    ->label(__('inventory_cost_layer.fully_consumed'))
                    ->query(fn (Builder $query): Builder => $query->where('remaining_quantity', '=', 0))
                    ->toggle(),
            ])
            ->headerActions([
                // Cost layers are created automatically, no manual creation
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                // No edit or delete actions - cost layers are immutable
            ])
            ->bulkActions([
                // No bulk actions - cost layers are immutable
            ])
            ->emptyStateHeading(__('inventory_cost_layer.no_cost_layers'))
            ->emptyStateDescription(__('inventory_cost_layer.no_cost_layers_description'))
            ->emptyStateIcon('heroicon-o-cube-transparent');
    }

    public function isReadOnly(): bool
    {
        return true; // Cost layers are read-only
    }
}
