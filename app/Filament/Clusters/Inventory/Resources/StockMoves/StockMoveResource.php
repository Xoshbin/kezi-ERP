<?php

namespace App\Filament\Clusters\Inventory\Resources\StockMoves;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Inventory\Resources\StockMoves\Pages\ListStockMoves;
use App\Filament\Clusters\Inventory\Resources\StockMoves\Pages\CreateStockMove;
use App\Filament\Clusters\Inventory\Resources\StockMoves\Pages\ViewStockMove;
use App\Filament\Clusters\Inventory\Resources\StockMoves\Pages\EditStockMove;
use App\Actions\Inventory\CreateStockMoveAction;
use App\Actions\Inventory\UpdateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\DataTransferObjects\Inventory\UpdateStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Filament\Clusters\Inventory\Resources\StockMoveResource\Pages;
use App\Models\Company;
use App\Models\StockMove;
use App\Filament\Clusters\Inventory\InventoryCluster;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMoveResource extends Resource
{
    protected static ?string $model = StockMove::class;

    protected static ?string $cluster = InventoryCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path';

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

    public static function form(Schema $schema): Schema
    {
        $company = Company::first();

        return $schema->components([
            Section::make(__('stock_move.basic_information'))
                ->description(__('stock_move.basic_information_description'))
                ->icon('heroicon-o-arrow-path')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('company_id')
                            ->relationship('company', 'name')
                            ->label(__('stock_move.company'))
                            ->required()
                            ->searchable()
                            ->default($company?->id),
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->label(__('stock_move.product'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('reference')
                            ->label(__('stock_move.reference'))
                            ->maxLength(255)
                            ->placeholder(__('stock_move.reference_placeholder')),
                    ]),
                    Grid::make(2)->schema([
                        Select::make('from_location_id')
                            ->relationship('fromLocation', 'name')
                            ->label(__('stock_move.from_location'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('to_location_id')
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
                    Grid::make(3)->schema([
                        TextInput::make('quantity')
                            ->label(__('stock_move.quantity'))
                            ->required()
                            ->numeric()
                            ->minValue(0.0001)
                            ->step(0.0001),
                        Select::make('move_type')
                            ->label(__('stock_move.move_type'))
                            ->required()
                            ->options(
                                collect(StockMoveType::cases())
                                    ->mapWithKeys(fn (StockMoveType $type) => [$type->value => $type->label()])
                            )
                            ->searchable(),
                        Select::make('status')
                            ->label(__('stock_move.status'))
                            ->required()
                            ->options(
                                collect(StockMoveStatus::cases())
                                    ->mapWithKeys(fn (StockMoveStatus $status) => [$status->value => $status->label()])
                            )
                            ->default(StockMoveStatus::Draft->value)
                            ->searchable(),
                    ]),
                    DatePicker::make('move_date')
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
                TextColumn::make('company.name')
                    ->label(__('stock_move.company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('move_date')
                    ->label(__('stock_move.move_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('reference')
                    ->label(__('stock_move.reference'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),
                TextColumn::make('product.name')
                    ->label(__('stock_move.product'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('fromLocation.name')
                    ->label(__('stock_move.from_location'))
                    ->searchable()
                    ->limit(20),
                TextColumn::make('toLocation.name')
                    ->label(__('stock_move.to_location'))
                    ->searchable()
                    ->limit(20),
                TextColumn::make('quantity')
                    ->label(__('stock_move.quantity'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('move_type')
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
                TextColumn::make('status')
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
                TextColumn::make('source_type')
                    ->label(__('stock_move.source'))
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('stock_move.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('move_date', 'desc')
            ->filters([
                SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('stock_move.company'))
                    ->multiple()
                    ->preload(),
                SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->label(__('stock_move.product'))
                    ->multiple()
                    ->preload(),
                SelectFilter::make('move_type')
                    ->label(__('stock_move.move_type'))
                    ->options(
                        collect(StockMoveType::cases())
                            ->mapWithKeys(fn (StockMoveType $type) => [$type->value => $type->label()])
                    )
                    ->multiple(),
                SelectFilter::make('status')
                    ->label(__('stock_move.status'))
                    ->options(
                        collect(StockMoveStatus::cases())
                            ->mapWithKeys(fn (StockMoveStatus $status) => [$status->value => $status->label()])
                    )
                    ->multiple(),
                Filter::make('move_date')
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('stock_move.from_date')),
                        DatePicker::make('until')
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
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (StockMove $record): bool => $record->status === StockMoveStatus::Draft),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(fn (StockMove $record): bool => $record->status === StockMoveStatus::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
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
            'index' => ListStockMoves::route('/'),
            'create' => CreateStockMove::route('/create'),
            'view' => ViewStockMove::route('/{record}'),
            'edit' => EditStockMove::route('/{record}/edit'),
        ];
    }
}
