<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\DatePicker;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Enums\Inventory\StockPickingType;

class StockPickingsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockPickings';

    protected static ?string $recordTitleAttribute = 'reference';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('reference')
                    ->label(__('inventory::landed_cost.fields.reference'))
                    ->disabled(),

                DatePicker::make('scheduled_date')
                    ->label(__('inventory::landed_cost.fields.scheduled_date'))
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label(__('inventory::landed_cost.fields.reference'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('inventory::landed_cost.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (StockPickingType $state) => $state->label()),

                TextColumn::make('state')
                    ->label(__('inventory::landed_cost.fields.state'))
                    ->badge()
                    ->formatStateUsing(fn (StockPickingState $state) => $state->label())
                    ->color(fn (StockPickingState $state): string => match ($state) {
                        StockPickingState::Done => 'success',
                        StockPickingState::InProgress => 'warning',
                        StockPickingState::Cancelled => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('scheduled_date')
                    ->label(__('inventory::landed_cost.fields.scheduled_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('source_location.name')
                    ->label(__('inventory::landed_cost.fields.from'))
                    ->sortable(),

                TextColumn::make('destination_location.name')
                    ->label(__('inventory::landed_cost.fields.to'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label(__('inventory::landed_cost.fields.attach_stock_picking'))
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn ($query) => $query->where('state', StockPickingState::Done)
                        ->where('type', StockPickingType::Receipt)),
            ])
            ->actions([
                DetachAction::make(),
            ])
            ->bulkActions([
                DetachBulkAction::make(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->latest('scheduled_date'));
    }
}
