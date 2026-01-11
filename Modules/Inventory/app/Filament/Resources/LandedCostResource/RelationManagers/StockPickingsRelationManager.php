<?php

namespace Modules\Inventory\Filament\Resources\LandedCostResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\DatePicker;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Actions\DetachBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Enums\Inventory\StockPickingType;

class StockPickingsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockPickings';

    protected static ?string $recordTitleAttribute = 'reference';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('reference')
                    ->label('Reference')
                    ->disabled(),

                DatePicker::make('scheduled_date')
                    ->label('Scheduled Date')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (StockPickingType $state) => $state->label()),

                TextColumn::make('state')
                    ->label('State')
                    ->badge()
                    ->formatStateUsing(fn (StockPickingState $state) => $state->label())
                    ->color(fn (StockPickingState $state): string => match ($state) {
                        StockPickingState::Done => 'success',
                        StockPickingState::InProgress => 'warning',
                        StockPickingState::Cancelled => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('scheduled_date')
                    ->label('Scheduled Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('source_location.name')
                    ->label('From')
                    ->sortable(),

                TextColumn::make('destination_location.name')
                    ->label('To')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Attach Stock Picking')
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
