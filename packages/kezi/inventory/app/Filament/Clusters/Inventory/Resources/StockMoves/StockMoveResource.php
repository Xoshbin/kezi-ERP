<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Kezi\Foundation\Filament\Components\CostPreviewComponent;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Filament\Clusters\Inventory\InventoryCluster;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Actions\ConfirmStockMoveAction;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Pages\CreateStockMove;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Pages\EditStockMove;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Pages\ListStockMoves;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Pages\ViewStockMove;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Sales\Models\Invoice;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class StockMoveResource extends Resource
{
    protected static ?string $model = StockMove::class;

    protected static ?string $cluster = InventoryCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('inventory::navigation.groups.operations');
    }

    public static function getModelLabel(): string
    {
        return __('inventory::stock_move.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inventory::stock_move.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory::stock_move.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('inventory::stock_move.basic_information'))
                ->description(__('inventory::stock_move.basic_information_description'))
                ->icon('heroicon-o-arrow-path')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('reference')
                            ->label(__('inventory::stock_move.reference'))
                            ->maxLength(255)
                            ->placeholder(__('inventory::stock_move.reference_placeholder')),
                        Textarea::make('description')
                            ->label(__('inventory::stock_move.description'))
                            ->rows(2)
                            ->maxLength(500),
                    ]),
                ]),

            Section::make(__('inventory::stock_move.product_lines'))
                ->description(__('inventory::stock_move.product_lines_description'))
                ->icon('heroicon-o-cube')
                ->schema([
                    Repeater::make('productLines')
                        ->label(__('inventory::stock_move.product_lines'))
                        ->schema([
                            Grid::make(2)->schema([
                                TranslatableSelect::forModel('product_id', Product::class)
                                    ->label(__('inventory::stock_move.product'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('quantity')
                                    ->label(__('inventory::stock_move.quantity'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.0001)
                                    ->step(0.0001),
                            ]),

                            // Cost Preview Component
                            CostPreviewComponent::forProductLine('product_id', 'quantity'),
                            Grid::make(2)->schema([
                                TranslatableSelect::forModel('from_location_id', StockLocation::class)
                                    ->label(__('inventory::stock_move.from_location'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                TranslatableSelect::forModel('to_location_id', StockLocation::class)
                                    ->label(__('inventory::stock_move.to_location'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                            ]),
                            Grid::make(2)->schema([
                                Select::make('source_type')
                                    ->label(__('inventory::stock_move.source_type'))
                                    ->options([
                                        Invoice::class => __('accounting::invoice.label'),
                                        VendorBill::class => __('accounting::bill.label'),
                                    ])
                                    ->reactive(),
                                Select::make('source_id')
                                    ->label(__('inventory::stock_move.source'))
                                    ->options(function (callable $get): array {
                                        $type = $get('source_type');
                                        if (! $type || ! class_exists($type)) {
                                            return [];
                                        }
                                        if ($type === Invoice::class) {
                                            return Invoice::query()
                                                ->selectRaw("id, COALESCE(invoice_number, CONCAT('#', id)) as display")
                                                ->orderByDesc('id')
                                                ->pluck('display', 'id')
                                                ->all();
                                        }
                                        if ($type === VendorBill::class) {
                                            return VendorBill::query()
                                                ->selectRaw("id, COALESCE(bill_reference, CONCAT('#', id)) as display")
                                                ->orderByDesc('id')
                                                ->pluck('display', 'id')
                                                ->all();
                                        }

                                        return [];
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn (callable $get) => ! $get('source_type')),
                            ]),
                            Textarea::make('description')
                                ->label(__('inventory::stock_move.line_description'))
                                ->rows(2)
                                ->maxLength(500),
                        ])
                        ->defaultItems(1)
                        ->addActionLabel(__('inventory::stock_move.add_product_line'))
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(
                            fn (array $state): ?string => isset($state['product_id'])
                                ? Product::find($state['product_id'])?->name ?? __('inventory::stock_move.new_product_line')
                                : __('inventory::stock_move.new_product_line')
                        ),
                ]),

            // Cost Summary Section
            Section::make(__('Cost Summary'))
                ->description(__('Estimated cost impact of this stock movement'))
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    CostPreviewComponent::forStockMove(),
                ])
                ->visible(fn (callable $get) => $get('move_type') === StockMoveType::Incoming->value),

            Section::make(__('inventory::stock_move.movement_details'))
                ->description(__('inventory::stock_move.movement_details_description'))
                ->icon('heroicon-o-calendar')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('move_type')
                            ->label(__('inventory::stock_move.move_type'))
                            ->required()
                            ->options(
                                collect(StockMoveType::cases())
                                    ->mapWithKeys(fn (StockMoveType $type) => [$type->value => $type->label()])
                            )
                            ->searchable(),
                        Select::make('status')
                            ->label(__('inventory::stock_move.status'))
                            ->required()
                            ->options(
                                collect(StockMoveStatus::cases())
                                    ->mapWithKeys(fn (StockMoveStatus $status) => [$status->value => $status->label()])
                            )
                            ->default(StockMoveStatus::Draft->value)
                            ->searchable(),
                        DatePicker::make('move_date')
                            ->label(__('inventory::stock_move.move_date'))
                            ->required()
                            ->default(now()),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('inventory::stock_move.company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('move_date')
                    ->label(__('inventory::stock_move.move_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('reference')
                    ->label(__('inventory::stock_move.reference'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),
                TextColumn::make('description')
                    ->label(__('inventory::stock_move.description'))
                    ->searchable()
                    ->limit(30)
                    ->placeholder('-'),
                TextColumn::make('productLines')
                    ->label(__('inventory::stock_move.products'))
                    ->formatStateUsing(function (StockMove $record): string {
                        $count = $record->productLines()->count();
                        if ($count === 0) {
                            return '-';
                        }
                        if ($count === 1) {
                            $productLine = $record->productLines()->first();

                            return $productLine ? $productLine->product->name : '-';
                        }

                        return "{$count} ".__('inventory::stock_move.products_count');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('productLines.product', function (Builder $query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('total_quantity')
                    ->label(__('inventory::stock_move.total_quantity'))
                    ->formatStateUsing(function (StockMove $record): string {
                        $total = $record->productLines()->sum('quantity');

                        return number_format($total, 4);
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withSum('productLines', 'quantity')
                            ->orderBy('product_lines_sum_quantity', $direction);
                    }),
                TextColumn::make('move_type')
                    ->label(__('inventory::stock_move.move_type'))
                    ->badge()
                    ->formatStateUsing(fn (StockMoveType $state): string => $state->label())
                    ->color(fn (StockMoveType $state): string => match ($state) {
                        StockMoveType::Incoming => 'success',
                        StockMoveType::Outgoing => 'danger',
                        StockMoveType::InternalTransfer => 'info',
                        StockMoveType::Adjustment => 'warning',
                    }),
                TextColumn::make('status')
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
            ->defaultSort('move_date', 'desc')
            ->filters([
                SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('inventory::stock_move.company'))
                    ->multiple()
                    ->preload(),
                SelectFilter::make('product_id')
                    ->label(__('inventory::stock_move.product'))
                    ->multiple()
                    ->preload()
                    ->options(function () {
                        return Product::query()
                            ->whereHas('stockMoveProductLines')
                            ->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('productLines', function (Builder $query) use ($data) {
                            $query->whereIn('product_id', $data['values']);
                        });
                    }),
                SelectFilter::make('move_type')
                    ->label(__('inventory::stock_move.move_type'))
                    ->options(
                        collect(StockMoveType::cases())
                            ->mapWithKeys(fn (StockMoveType $type) => [$type->value => $type->label()])
                    )
                    ->multiple(),
                SelectFilter::make('status')
                    ->label(__('inventory::stock_move.status'))
                    ->options(
                        collect(StockMoveStatus::cases())
                            ->mapWithKeys(fn (StockMoveStatus $status) => [$status->value => $status->label()])
                    )
                    ->multiple(),
                Filter::make('move_date')
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('inventory::stock_move.from_date')),
                        DatePicker::make('until')
                            ->label(__('inventory::stock_move.until_date')),
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
                ConfirmStockMoveAction::make()
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('inventory::stock_move.basic_information'))
                ->description(__('inventory::stock_move.basic_information_description'))
                ->icon('heroicon-o-arrow-path')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('reference')
                            ->label(__('inventory::stock_move.reference'))
                            ->placeholder('-'),
                        TextEntry::make('move_date')
                            ->label(__('inventory::stock_move.move_date'))
                            ->date(),
                        TextEntry::make('status')
                            ->label(__('inventory::stock_move.status'))
                            ->badge()
                            ->color(fn (StockMoveStatus $state): string => match ($state) {
                                StockMoveStatus::Draft => 'gray',
                                StockMoveStatus::Done => 'success',
                                StockMoveStatus::Cancelled => 'danger',
                            }),
                    ]),
                    Grid::make(2)->schema([
                        TextEntry::make('move_type')
                            ->label(__('inventory::stock_move.move_type'))
                            ->badge()
                            ->color(fn (StockMoveType $state): string => match ($state) {
                                StockMoveType::Incoming => 'success',
                                StockMoveType::Outgoing => 'danger',
                                StockMoveType::InternalTransfer => 'info',
                                StockMoveType::Adjustment => 'warning',
                            }),
                        TextEntry::make('description')
                            ->label(__('inventory::stock_move.description'))
                            ->placeholder('-'),
                    ]),
                ]),

            Section::make(__('inventory::stock_move.product_lines'))
                ->description(__('inventory::stock_move.product_lines_description'))
                ->icon('heroicon-o-cube')
                ->schema([
                    RepeatableEntry::make('productLines')
                        ->label(__('inventory::stock_move.product_lines'))
                        ->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('product.name')
                                    ->label(__('inventory::stock_move.product')),
                                TextEntry::make('quantity')
                                    ->label(__('inventory::stock_move.quantity'))
                                    ->numeric(decimalPlaces: 4),
                            ]),
                            Grid::make(2)->schema([
                                TextEntry::make('fromLocation.name')
                                    ->label(__('inventory::stock_move.from_location'))
                                    ->placeholder('-'),
                                TextEntry::make('toLocation.name')
                                    ->label(__('inventory::stock_move.to_location'))
                                    ->placeholder('-'),
                            ]),
                        ])
                        ->columns(1),
                ])
                ->visible(fn (StockMove $record): bool => $record->productLines()->exists()),

            Section::make(__('inventory::stock_move.audit_information'))
                ->description(__('inventory::stock_move.audit_information_description'))
                ->icon('heroicon-o-clock')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('createdByUser.name')
                            ->label(__('inventory::stock_move.created_by'))
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label(__('inventory::stock_move.created_at'))
                            ->dateTime(),
                    ]),
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
