<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Product\Models\Product;

class StockMovesRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMoveProductLines';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('product.stock_moves');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('from_location_id')
                        ->relationship('fromLocation', 'name')
                        ->label(__('inventory::stock_move.from_location'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('to_location_id')
                        ->relationship('toLocation', 'name')
                        ->label(__('inventory::stock_move.to_location'))
                        ->required()
                        ->searchable()
                        ->preload(),
                ]),
                Grid::make(3)->schema([
                    TextInput::make('quantity')
                        ->label(__('inventory::stock_move.quantity'))
                        ->required()
                        ->numeric()
                        ->minValue(0.0001),
                    Select::make('move_type')
                        ->label(__('inventory::stock_move.move_type'))
                        ->required()
                        ->options(
                            collect(StockMoveType::cases())
                                ->mapWithKeys(fn (StockMoveType $type) => [$type->value => $type->label()])
                        ),
                    Select::make('status')
                        ->label(__('inventory::stock_move.status'))
                        ->required()
                        ->options(
                            collect(StockMoveStatus::cases())
                                ->mapWithKeys(fn (StockMoveStatus $status) => [$status->value => $status->label()])
                        )
                        ->default(StockMoveStatus::Draft->value),
                ]),
                Grid::make(2)->schema([
                    DatePicker::make('move_date')
                        ->label(__('inventory::stock_move.move_date'))
                        ->required()
                        ->default(now()),
                    TextInput::make('reference')
                        ->label(__('inventory::stock_move.reference'))
                        ->maxLength(255),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('stockMove.move_date')
                    ->label(__('inventory::stock_move.move_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('stockMove.reference')
                    ->label(__('inventory::stock_move.reference'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('fromLocation.name')
                    ->label(__('inventory::stock_move.from_location'))
                    ->searchable(),
                TextColumn::make('toLocation.name')
                    ->label(__('inventory::stock_move.to_location'))
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label(__('inventory::stock_move.quantity'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('stockMove.move_type')
                    ->label(__('inventory::stock_move.move_type'))
                    ->badge()
                    ->formatStateUsing(fn (StockMoveType $state): string => $state->label())
                    ->color(fn (StockMoveType $state): string => match ($state) {
                        StockMoveType::Incoming => 'success',
                        StockMoveType::Outgoing => 'danger',
                        StockMoveType::InternalTransfer => 'info',
                        StockMoveType::Adjustment => 'warning',
                    }),
                TextColumn::make('stockMove.status')
                    ->label(__('inventory::stock_move.status'))
                    ->badge()
                    ->formatStateUsing(fn (StockMoveStatus $state): string => $state->label())
                    ->color(fn (StockMoveStatus $state): string => match ($state) {
                        StockMoveStatus::Draft => 'gray',
                        StockMoveStatus::Confirmed => 'warning',
                        StockMoveStatus::Done => 'success',
                        StockMoveStatus::Cancelled => 'danger',
                    }),
                TextColumn::make('source_type')
                    ->label(__('inventory::stock_move.source'))
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('inventory::stock_move.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('move_type')
                    ->label(__('inventory::stock_move.move_type'))
                    ->options(
                        collect(StockMoveType::cases())
                            ->mapWithKeys(fn (StockMoveType $type) => [$type->value => $type->label()])
                    ),
                SelectFilter::make('status')
                    ->label(__('inventory::stock_move.status'))
                    ->options(
                        collect(StockMoveStatus::cases())
                            ->mapWithKeys(fn (StockMoveStatus $status) => [$status->value => $status->label()])
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('inventory::stock_move.add_product_line'))
                    ->icon('heroicon-o-plus')
                    ->mutateDataUsing(function (array $data): array {
                        /** @var Product $owner */
                        $owner = $this->getOwnerRecord();
                        $data['company_id'] = $owner->getAttribute('company_id');
                        $data['product_id'] = $owner->getKey();
                        $data['created_by_user_id'] = Filament::auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (StockMoveProductLine $record): bool => $record->stockMove?->status === StockMoveStatus::Draft),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(fn (StockMoveProductLine $record): bool => $record->stockMove?->status === StockMoveStatus::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }
}
