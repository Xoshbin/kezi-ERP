<?php

namespace App\Filament\Clusters\Inventory\Resources;

use App\Actions\Inventory\CreateStockMoveAction;
use App\Actions\Inventory\UpdateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\DataTransferObjects\Inventory\UpdateStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Filament\Clusters\Inventory\Resources\StockMoveResource\Pages;
use App\Models\Company;
use App\Models\StockMove;
use App\Filament\Clusters\Inventory;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMoveResource extends Resource
{
    protected static ?string $model = StockMove::class;

    protected static ?string $cluster = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('stock_move.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('stock_move.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('stock_move.plural_label');
    }

    public static function form(Form $form): Form
    {
        $company = Company::first();

        return $form->schema([
            Section::make(__('stock_move.basic_information'))
                ->description(__('stock_move.basic_information_description'))
                ->icon('heroicon-o-arrow-path')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->label(__('stock_move.product'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('reference')
                            ->label(__('stock_move.reference'))
                            ->maxLength(255)
                            ->placeholder(__('stock_move.reference_placeholder')),
                    ]),
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
                ]),

            Section::make(__('stock_move.movement_details'))
                ->description(__('stock_move.movement_details_description'))
                ->icon('heroicon-o-cube')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label(__('stock_move.quantity'))
                            ->required()
                            ->numeric()
                            ->minValue(0.0001)
                            ->step(0.0001),
                        Forms\Components\Select::make('move_type')
                            ->label(__('stock_move.move_type'))
                            ->required()
                            ->options(
                                collect(StockMoveType::cases())
                                    ->mapWithKeys(fn (StockMoveType $type) => [$type->value => $type->label()])
                            )
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->label(__('stock_move.status'))
                            ->required()
                            ->options(
                                collect(StockMoveStatus::cases())
                                    ->mapWithKeys(fn (StockMoveStatus $status) => [$status->value => $status->label()])
                            )
                            ->default(StockMoveStatus::Draft->value)
                            ->searchable(),
                    ]),
                    Forms\Components\DatePicker::make('move_date')
                        ->label(__('stock_move.move_date'))
                        ->required()
                        ->default(now()),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('stock_move.company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('move_date')
                    ->label(__('stock_move.move_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('stock_move.reference'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('stock_move.product'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('fromLocation.name')
                    ->label(__('stock_move.from_location'))
                    ->searchable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('toLocation.name')
                    ->label(__('stock_move.to_location'))
                    ->searchable()
                    ->limit(20),
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
                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('stock_move.company'))
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->label(__('stock_move.product'))
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('move_type')
                    ->label(__('stock_move.move_type'))
                    ->options(
                        collect(StockMoveType::cases())
                            ->mapWithKeys(fn (StockMoveType $type) => [$type->value => $type->label()])
                    )
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('stock_move.status'))
                    ->options(
                        collect(StockMoveStatus::cases())
                            ->mapWithKeys(fn (StockMoveStatus $status) => [$status->value => $status->label()])
                    )
                    ->multiple(),
                Tables\Filters\Filter::make('move_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('stock_move.from_date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('stock_move.until_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('move_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('move_date', '<=', $date),
                            );
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMoves::route('/'),
            'create' => Pages\CreateStockMove::route('/create'),
            'view' => Pages\ViewStockMove::route('/{record}'),
            'edit' => Pages\EditStockMove::route('/{record}/edit'),
        ];
    }
}
