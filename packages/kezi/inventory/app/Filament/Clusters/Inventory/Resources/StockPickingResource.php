<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Enums\Inventory\StockPickingType;
use Kezi\Inventory\Filament\Clusters\Inventory\InventoryCluster;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\CreateStockPicking;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\EditStockPicking;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ListStockPickings;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ViewStockPicking;
use Kezi\Inventory\Models\StockPicking;

class StockPickingResource extends Resource
{
    protected static ?string $model = StockPicking::class;

    protected static ?string $cluster = InventoryCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('inventory::navigation.groups.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory::stock_picking.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('inventory::stock_picking.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inventory::stock_picking.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('inventory::stock_move.basic_information'))
                ->schema([
                    Forms\Components\TextInput::make('reference')
                        ->label(__('inventory::stock_picking.reference'))
                        ->required()
                        ->maxLength(255)
                        ->default(fn () => 'SP-'.str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT)),

                    Forms\Components\Select::make('type')
                        ->label(__('inventory::stock_picking.fields.type'))
                        ->options(StockPickingType::class)
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('state')
                        ->label(__('inventory::stock_picking.fields.state'))
                        ->options(StockPickingState::class)
                        ->default(StockPickingState::Draft)
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('partner_id')
                        ->label(__('inventory::stock_picking.partner'))
                        ->relationship('partner', 'name')
                        ->searchable()
                        ->preload(),

                    Forms\Components\DateTimePicker::make('scheduled_date')
                        ->label(__('inventory::stock_picking.scheduled_date'))
                        ->default(now())
                        ->required(),

                    Forms\Components\TextInput::make('origin')
                        ->label(__('inventory::stock_picking.origin'))
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make(__('inventory::stock_picking.operations'))
                ->schema([
                    Forms\Components\Repeater::make('stockMoves')
                        ->relationship()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => 'Move ('.(isset($state['productLines']) ? count($state['productLines']) : 0).' lines)')
                        ->deleteAction(fn ($action) => $action->requiresConfirmation())
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data, StockPicking $record): array {
                            $data['company_id'] = $record->company_id;
                            $data['created_by_user_id'] = \Illuminate\Support\Facades\Auth::id();
                            $data['move_date'] = $record->scheduled_date;
                            $data['status'] = \Kezi\Inventory\Enums\Inventory\StockMoveStatus::Draft;

                            $data['move_type'] = match ($record->type) {
                                StockPickingType::Receipt => \Kezi\Inventory\Enums\Inventory\StockMoveType::Incoming,
                                StockPickingType::Delivery => \Kezi\Inventory\Enums\Inventory\StockMoveType::Outgoing,
                                StockPickingType::Internal => \Kezi\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
                                default => \Kezi\Inventory\Enums\Inventory\StockMoveType::InternalTransfer,
                            };

                            return $data;
                        })
                        ->schema([
                            Forms\Components\Textarea::make('description')
                                ->label(__('Description'))
                                ->rows(1)
                                ->columnSpanFull(),

                            Forms\Components\Repeater::make('productLines')
                                ->label(__('Product Lines'))
                                ->relationship('productLines')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            \Xoshbin\TranslatableSelect\Components\TranslatableSelect::forModel('product_id', \Kezi\Product\Models\Product::class)
                                                ->label(__('Product'))
                                                ->required()
                                                ->searchable()
                                                ->preload(),

                                            Forms\Components\TextInput::make('quantity')
                                                ->label(__('Quantity'))
                                                ->numeric()
                                                ->required()
                                                ->default(1),
                                        ]),

                                    Grid::make(2)
                                        ->schema([
                                            \Xoshbin\TranslatableSelect\Components\TranslatableSelect::forModel('from_location_id', \Kezi\Inventory\Models\StockLocation::class)
                                                ->label(__('From Location'))
                                                ->searchable()
                                                ->preload()
                                                ->required(),

                                            \Xoshbin\TranslatableSelect\Components\TranslatableSelect::forModel('to_location_id', \Kezi\Inventory\Models\StockLocation::class)
                                                ->label(__('To Location'))
                                                ->searchable()
                                                ->preload()
                                                ->required(),
                                        ]),
                                    Forms\Components\Textarea::make('description')
                                        ->label(__('Description'))
                                        ->rows(1)
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['product_id'] ? \Kezi\Product\Models\Product::find($state['product_id'])?->name : null)
                                ->deleteAction(fn ($action) => $action->requiresConfirmation())
                                ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                    $data['company_id'] = \Filament\Facades\Filament::getTenant()->id ?? \App\Models\Company::first()->id;

                                    return $data;
                                }),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('inventory::stock_picking.reference'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('inventory::stock_picking.fields.type'))
                    ->badge()
                    ->color(fn (StockPickingType $state): string => match ($state) {
                        StockPickingType::Receipt => 'success',
                        StockPickingType::Delivery => 'danger',
                        StockPickingType::Internal => 'info',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('state')
                    ->label(__('inventory::stock_picking.fields.state'))
                    ->badge()
                    ->color(fn (StockPickingState $state): string => match ($state) {
                        StockPickingState::Draft => 'gray',
                        StockPickingState::Confirmed => 'warning',
                        StockPickingState::Assigned => 'info',
                        StockPickingState::Done => 'success',
                        StockPickingState::Cancelled => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('partner.name')
                    ->label(__('inventory::stock_picking.partner'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label(__('inventory::stock_picking.scheduled_date'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label(__('inventory::stock_picking.completed_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('origin')
                    ->label(__('inventory::stock_picking.origin'))
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('stockMoves')
                    ->label(__('inventory::stock_picking.moves'))
                    ->counts('stockMoves')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('inventory::stock_picking.fields.type'))
                    ->options(StockPickingType::class),

                Tables\Filters\SelectFilter::make('state')
                    ->label(__('inventory::stock_picking.fields.state'))
                    ->options(StockPickingState::class),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (StockPicking $record): bool => $record->state === StockPickingState::Draft),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(fn (StockPicking $record): bool => $record->state === StockPickingState::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => ListStockPickings::route('/'),
            'create' => CreateStockPicking::route('/create'),
            'view' => ViewStockPicking::route('/{record}'),
            'edit' => EditStockPicking::route('/{record}/edit'),
            'validate' => \Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ValidateStockPicking::route('/{record}/validate'),
        ];
    }
}
