<?php

namespace Modules\Manufacturing\Filament\Resources;

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
use Modules\Manufacturing\Filament\Resources\ManufacturingOrderResource\Pages;
use Modules\Manufacturing\Filament\Resources\ManufacturingOrderResource\RelationManagers;
use Modules\Manufacturing\Models\ManufacturingOrder;

class ManufacturingOrderResource extends Resource
{
    protected static ?string $model = ManufacturingOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'Manufacturing Order';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Manufacturing Orders';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Manufacturing Order Information')
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label('MO Number')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Auto-generated on save')
                        ->visible(fn ($record) => $record !== null),

                    Forms\Components\Select::make('bom_id')
                        ->label('Bill of Materials')
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
                        ->label('Product to Manufacture')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn ($get) => $get('bom_id') !== null),

                    Forms\Components\TextInput::make('quantity_to_produce')
                        ->label('Quantity to Produce')
                        ->numeric()
                        ->required()
                        ->minValue(0.0001)
                        ->default(1.0),

                    Forms\Components\Select::make('source_location_id')
                        ->label('Source Location (Components)')
                        ->relationship('sourceLocation', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Where to take components from'),

                    Forms\Components\Select::make('destination_location_id')
                        ->label('Destination Location (Finished Goods)')
                        ->relationship('destinationLocation', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Where to put finished products'),

                    Forms\Components\DatePicker::make('planned_start_date')
                        ->label('Planned Start Date')
                        ->default(now()),

                    Forms\Components\DatePicker::make('planned_end_date')
                        ->label('Planned End Date')
                        ->default(now()->addDays(7)),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Production Status')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            ManufacturingOrderStatus::Draft->value => 'Draft',
                            ManufacturingOrderStatus::Confirmed->value => 'Confirmed',
                            ManufacturingOrderStatus::InProgress->value => 'In Progress',
                            ManufacturingOrderStatus::Done->value => 'Done',
                            ManufacturingOrderStatus::Cancelled->value => 'Cancelled',
                        ])
                        ->default(ManufacturingOrderStatus::Draft->value)
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('quantity_produced')
                        ->label('Quantity Produced')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->default(0),

                    Forms\Components\DateTimePicker::make('actual_start_date')
                        ->label('Actual Start Date')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\DateTimePicker::make('actual_end_date')
                        ->label('Actual End Date')
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
                    ->label('MO Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_to_produce')
                    ->label('Qty to Produce')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_produced')
                    ->label('Qty Produced')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => ManufacturingOrderStatus::Draft->value,
                        'warning' => ManufacturingOrderStatus::Confirmed->value,
                        'primary' => ManufacturingOrderStatus::InProgress->value,
                        'success' => ManufacturingOrderStatus::Done->value,
                        'danger' => ManufacturingOrderStatus::Cancelled->value,
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('planned_start_date')
                    ->label('Planned Start')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('actual_start_date')
                    ->label('Actual Start')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        ManufacturingOrderStatus::Draft->value => 'Draft',
                        ManufacturingOrderStatus::Confirmed->value => 'Confirmed',
                        ManufacturingOrderStatus::InProgress->value => 'In Progress',
                        ManufacturingOrderStatus::Done->value => 'Done',
                        ManufacturingOrderStatus::Cancelled->value => 'Cancelled',
                    ]),

                Tables\Filters\Filter::make('planned_start_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until'),
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

    public static function getNavigationGroup(): ?string
    {
        return 'Manufacturing';
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
