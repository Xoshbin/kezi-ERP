<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\ManufacturingCluster;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\Pages;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\RelationManagers;
use Modules\Manufacturing\Models\ManufacturingOrder;

class ManufacturingOrderResource extends Resource
{
    protected static ?string $model = ManufacturingOrder::class;

    protected static ?string $cluster = ManufacturingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('manufacturing::manufacturing.order.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('manufacturing::manufacturing.order.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('manufacturing::manufacturing.order.section_information'))
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label(__('manufacturing::manufacturing.order.number'))
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder(__('manufacturing::manufacturing.order.number_placeholder'))
                        ->visible(fn ($record) => $record !== null),

                    Forms\Components\Select::make('bom_id')
                        ->label(__('manufacturing::manufacturing.bom.label'))
                        ->relationship('billOfMaterial', 'code')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $bom = \Modules\Manufacturing\Models\BillOfMaterial::find($state);
                                if ($bom) {
                                    $set('product_id', $bom->product_id);
                                }
                            }
                        }),

                    Forms\Components\Select::make('product_id')
                        ->label(__('manufacturing::manufacturing.order.product'))
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn ($get) => $get('bom_id') !== null)
                        ->dehydrated(),

                    Forms\Components\TextInput::make('quantity_to_produce')
                        ->label(__('manufacturing::manufacturing.order.quantity_to_produce'))
                        ->numeric()
                        ->required()
                        ->minValue(0.0001)
                        ->default(1.0),

                    Forms\Components\Select::make('source_location_id')
                        ->label(__('manufacturing::manufacturing.order.source_location'))
                        ->relationship('sourceLocation', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText(__('manufacturing::manufacturing.order.source_location_helper')),

                    Forms\Components\Select::make('destination_location_id')
                        ->label(__('manufacturing::manufacturing.order.destination_location'))
                        ->relationship('destinationLocation', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText(__('manufacturing::manufacturing.order.destination_location_helper')),

                    Forms\Components\DatePicker::make('planned_start_date')
                        ->label(__('manufacturing::manufacturing.order.planned_start_date'))
                        ->default(now()),

                    Forms\Components\DatePicker::make('planned_end_date')
                        ->label(__('manufacturing::manufacturing.order.planned_end_date'))
                        ->default(now()->addDays(7)),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('manufacturing::manufacturing.bom.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make(__('manufacturing::manufacturing.order.production_status'))
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label(__('manufacturing::manufacturing.order.status'))
                        ->options([
                            ManufacturingOrderStatus::Draft->value => __('manufacturing::manufacturing.order.draft'),
                            ManufacturingOrderStatus::Confirmed->value => __('manufacturing::manufacturing.order.confirmed'),
                            ManufacturingOrderStatus::InProgress->value => __('manufacturing::manufacturing.order.in_progress'),
                            ManufacturingOrderStatus::Done->value => __('manufacturing::manufacturing.order.done'),
                            ManufacturingOrderStatus::Cancelled->value => __('manufacturing::manufacturing.order.cancelled'),
                        ])
                        ->default(ManufacturingOrderStatus::Draft->value)
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('quantity_produced')
                        ->label(__('manufacturing::manufacturing.order.quantity_produced'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->default(0),

                    Forms\Components\DateTimePicker::make('actual_start_date')
                        ->label(__('manufacturing::manufacturing.order.actual_start_date'))
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\DateTimePicker::make('actual_end_date')
                        ->label(__('manufacturing::manufacturing.order.actual_end_date'))
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(2)
                ->visible(fn ($record) => $record !== null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label(__('manufacturing::manufacturing.order.number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('manufacturing::manufacturing.bom.product'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_to_produce')
                    ->label(__('manufacturing::manufacturing.order.qty_to_produce_short'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_produced')
                    ->label(__('manufacturing::manufacturing.order.qty_produced_short'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('manufacturing::manufacturing.order.status'))
                    ->colors([
                        'secondary' => ManufacturingOrderStatus::Draft->value,
                        'warning' => ManufacturingOrderStatus::Confirmed->value,
                        'primary' => ManufacturingOrderStatus::InProgress->value,
                        'success' => ManufacturingOrderStatus::Done->value,
                        'danger' => ManufacturingOrderStatus::Cancelled->value,
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('planned_start_date')
                    ->label(__('manufacturing::manufacturing.order.planned_start'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('actual_start_date')
                    ->label(__('manufacturing::manufacturing.order.actual_start'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('manufacturing::manufacturing.bom.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('manufacturing::manufacturing.order.status'))
                    ->options([
                        ManufacturingOrderStatus::Draft->value => __('manufacturing::manufacturing.order.draft'),
                        ManufacturingOrderStatus::Confirmed->value => __('manufacturing::manufacturing.order.confirmed'),
                        ManufacturingOrderStatus::InProgress->value => __('manufacturing::manufacturing.order.in_progress'),
                        ManufacturingOrderStatus::Done->value => __('manufacturing::manufacturing.order.done'),
                        ManufacturingOrderStatus::Cancelled->value => __('manufacturing::manufacturing.order.cancelled'),
                    ]),

                Tables\Filters\Filter::make('planned_start_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('manufacturing::manufacturing.order.from')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('manufacturing::manufacturing.order.until')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('planned_start_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('planned_start_date', '<=', $date));
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record->status === ManufacturingOrderStatus::Draft),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->status === ManufacturingOrderStatus::Draft),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LinesRelationManager::class,
            RelationManagers\WorkOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManufacturingOrders::route('/'),
            'create' => Pages\CreateManufacturingOrder::route('/create'),
            'view' => Pages\ViewManufacturingOrder::route('/{record}'),
            'edit' => Pages\EditManufacturingOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', ManufacturingOrderStatus::InProgress)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
