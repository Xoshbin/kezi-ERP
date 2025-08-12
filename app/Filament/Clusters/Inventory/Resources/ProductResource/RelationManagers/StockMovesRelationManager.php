<?php

namespace App\Filament\Clusters\Inventory\Resources\ProductResource\RelationManagers;

use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Models\StockMove;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovesRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMoves';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('product.stock_moves');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('from_location_id')
                        ->relationship('fromLocation', 'name')
                        ->label(__('stock_move.from_location'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('to_location_id')
                        ->relationship('toLocation', 'name')
                        ->label(__('stock_move.to_location'))
                        ->required()
                        ->searchable()
                        ->preload(),
                ]),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('quantity')
                        ->label(__('stock_move.quantity'))
                        ->required()
                        ->numeric()
                        ->minValue(0.0001),
                    Forms\Components\Select::make('move_type')
                        ->label(__('stock_move.move_type'))
                        ->required()
                        ->options(
                            collect(StockMoveType::cases())
                                ->mapWithKeys(fn (StockMoveType $type) => [$type->value => $type->label()])
                        ),
                    Forms\Components\Select::make('status')
                        ->label(__('stock_move.status'))
                        ->required()
                        ->options(
                            collect(StockMoveStatus::cases())
                                ->mapWithKeys(fn (StockMoveStatus $status) => [$status->value => $status->label()])
                        )
                        ->default(StockMoveStatus::Draft->value),
                ]),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\DatePicker::make('move_date')
                        ->label(__('stock_move.move_date'))
                        ->required()
                        ->default(now()),
                    Forms\Components\TextInput::make('reference')
                        ->label(__('stock_move.reference'))
                        ->maxLength(255),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                Tables\Columns\TextColumn::make('move_date')
                    ->label(__('stock_move.move_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('stock_move.reference'))
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('fromLocation.name')
                    ->label(__('stock_move.from_location'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('toLocation.name')
                    ->label(__('stock_move.to_location'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('stock_move.quantity'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('move_type')
                    ->label(__('stock_move.move_type'))
                    ->badge()
                    ->formatStateUsing(fn (StockMoveType $state): string => $state->label())
                    ->color(fn (StockMoveType $state): string => match ($state) {
                        StockMoveType::Incoming => 'success',
                        StockMoveType::Outgoing => 'danger',
                        StockMoveType::InternalTransfer => 'info',
                        StockMoveType::Adjustment => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('stock_move.status'))
                    ->badge()
                    ->formatStateUsing(fn (StockMoveStatus $state): string => $state->label())
                    ->color(fn (StockMoveStatus $state): string => match ($state) {
                        StockMoveStatus::Draft => 'gray',
                        StockMoveStatus::Confirmed => 'warning',
                        StockMoveStatus::Done => 'success',
                        StockMoveStatus::Cancelled => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('source_type')
                    ->label(__('stock_move.source'))
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('stock_move.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('move_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('move_type')
                    ->label(__('stock_move.move_type'))
                    ->options(
                        collect(StockMoveType::cases())
                            ->mapWithKeys(fn (StockMoveType $type) => [$type->value => $type->label()])
                    ),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('stock_move.status'))
                    ->options(
                        collect(StockMoveStatus::cases())
                            ->mapWithKeys(fn (StockMoveStatus $status) => [$status->value => $status->label()])
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = $this->getOwnerRecord()->company_id;
                        $data['product_id'] = $this->getOwnerRecord()->id;
                        $data['created_by_user_id'] = Filament::auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (StockMove $record): bool => $record->status === StockMoveStatus::Draft),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(fn (StockMove $record): bool => $record->status === StockMoveStatus::Draft),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }
}
