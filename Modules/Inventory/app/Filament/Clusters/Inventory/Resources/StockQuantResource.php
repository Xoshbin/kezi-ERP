<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources;

use App\Filament\Clusters\Inventory\InventoryCluster;
use App\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;
use App\Models\StockQuant;
use BackedEnum;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockQuantResource extends Resource
{
    protected static ?string $model = StockQuant::class;

    protected static ?string $cluster = InventoryCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('stock_quant.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('stock_quant.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('stock_quant.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('stock_quant.sections.basic_info'))
                ->schema([
                    Grid::make(3)->schema([
                        Forms\Components\Select::make('product_id')
                            ->label(__('stock_quant.fields.product'))
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('location_id')
                            ->label(__('stock_quant.fields.location'))
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('lot_id')
                            ->label(__('stock_quant.fields.lot'))
                            ->relationship('lot', 'lot_code')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),
                ]),

            Section::make(__('stock_quant.sections.quantities'))
                ->schema([
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label(__('stock_quant.fields.quantity'))
                            ->numeric()
                            ->step(0.0001)
                            ->minValue(0)
                            ->required()
                            ->default(0),

                        Forms\Components\TextInput::make('reserved_quantity')
                            ->label(__('stock_quant.fields.reserved_quantity'))
                            ->numeric()
                            ->step(0.0001)
                            ->minValue(0)
                            ->required()
                            ->default(0),

                        Forms\Components\TextInput::make('available_quantity')
                            ->label(__('stock_quant.fields.available_quantity'))
                            ->disabled()
                            ->dehydrated(false)
                            ->live()
                            ->default(function (Get $get): string {
                                $quantity = (float) ($get('quantity') ?? 0);
                                $reserved = (float) ($get('reserved_quantity') ?? 0);
                                $available = $quantity - $reserved;
                                return number_format($available, 4);
                            }),
                    ]),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('stock_quant.sections.basic_info'))
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('product.name')
                            ->label(__('stock_quant.fields.product')),

                        TextEntry::make('location.name')
                            ->label(__('stock_quant.fields.location')),

                        TextEntry::make('lot.lot_code')
                            ->label(__('stock_quant.fields.lot'))
                            ->placeholder(__('stock_quant.no_lot')),
                    ]),
                ]),

            Section::make(__('stock_quant.sections.quantities'))
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('quantity')
                            ->label(__('stock_quant.fields.quantity'))
                            ->numeric(decimalPlaces: 4),

                        TextEntry::make('reserved_quantity')
                            ->label(__('stock_quant.fields.reserved_quantity'))
                            ->numeric(decimalPlaces: 4),

                        TextEntry::make('available_quantity')
                            ->label(__('stock_quant.fields.available_quantity'))
                            ->getStateUsing(fn(StockQuant $record): float => $record->available_quantity)
                            ->numeric(decimalPlaces: 4)
                            ->color(fn(float $state): string => match (true) {
                                $state <= 0 => 'danger',
                                $state <= 10 => 'warning',
                                default => 'success',
                            }),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('stock_quant.fields.id'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('stock_quant.fields.product'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('location.name')
                    ->label(__('stock_quant.fields.location'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('lot.lot_code')
                    ->label(__('stock_quant.fields.lot'))
                    ->sortable()
                    ->searchable()
                    ->placeholder(__('stock_quant.no_lot')),

                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('stock_quant.fields.quantity'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),

                Tables\Columns\TextColumn::make('reserved_quantity')
                    ->label(__('stock_quant.fields.reserved_quantity'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),

                Tables\Columns\TextColumn::make('available_quantity')
                    ->label(__('stock_quant.fields.available_quantity'))
                    ->getStateUsing(fn(StockQuant $record): float => $record->available_quantity)
                    ->numeric(decimalPlaces: 4)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("(quantity - reserved_quantity) {$direction}");
                    })
                    ->color(fn(float $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('stock_quant.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product')
                    ->label(__('stock_quant.filters.product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('location')
                    ->label(__('stock_quant.filters.location'))
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('lot_id')
                    ->label(__('stock_quant.filters.lot'))
                    ->relationship('lot', 'lot_code')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('low_stock')
                    ->label(__('stock_quant.filters.low_stock'))
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereRaw('(quantity - reserved_quantity) <= 10')
                    ),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label(__('stock_quant.filters.out_of_stock'))
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereRaw('(quantity - reserved_quantity) <= 0')
                    ),

                Tables\Filters\Filter::make('with_reservations')
                    ->label(__('stock_quant.filters.with_reservations'))
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('reserved_quantity', '>', 0)
                    ),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading(__('stock_quant.empty_state.heading'))
            ->emptyStateDescription(__('stock_quant.empty_state.description'))
            ->emptyStateIcon('heroicon-o-cube');
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
            'index' => Pages\ListStockQuants::route('/'),
            'create' => Pages\CreateStockQuant::route('/create'),
            'view' => Pages\ViewStockQuant::route('/{record}'),
            'edit' => Pages\EditStockQuant::route('/{record}/edit'),
        ];
    }
}
