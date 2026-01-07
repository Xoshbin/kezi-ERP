<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Inventory\Enums\Inventory\SerialNumberStatus;
use Modules\Inventory\Filament\Clusters\Inventory\InventoryCluster;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\Pages\CreateSerialNumber;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\Pages\EditSerialNumber;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\Pages\ListSerialNumbers;
use Modules\Inventory\Models\SerialNumber;

class SerialNumberResource extends Resource
{
    protected static ?string $model = SerialNumber::class;

    protected static ?string $cluster = InventoryCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('inventory.serial_number');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inventory.serial_numbers');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('inventory.serial_number'))
                ->description(__('inventory.serial_number_information'))
                ->icon('heroicon-o-qr-code')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->label(__('product.label'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record !== null),

                        TextInput::make('serial_code')
                            ->label(__('inventory.serial_code'))
                            ->required()
                            ->maxLength(255)
                            ->unique(SerialNumber::class, 'serial_code', ignoreRecord: true)
                            ->disabled(fn ($record) => $record !== null),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('status')
                            ->label(__('inventory.warranty_status'))
                            ->options([
                                SerialNumberStatus::Available->value => __('inventory.serial_status_available'),
                                SerialNumberStatus::Reserved->value => __('inventory.serial_status_reserved'),
                                SerialNumberStatus::Sold->value => __('inventory.serial_status_sold'),
                                SerialNumberStatus::Returned->value => __('inventory.serial_status_returned'),
                                SerialNumberStatus::Defective->value => __('inventory.serial_status_defective'),
                            ])
                            ->default(SerialNumberStatus::Available->value)
                            ->required()
                            ->disabled(),

                        Select::make('current_location_id')
                            ->relationship('currentLocation', 'name')
                            ->label(__('inventory.current_location'))
                            ->searchable()
                            ->preload()
                            ->disabled(),
                    ]),

                    Grid::make(2)->schema([
                        DatePicker::make('warranty_start')
                            ->label(__('inventory.warranty_start'))
                            ->nullable(),

                        DatePicker::make('warranty_end')
                            ->label(__('inventory.warranty_end'))
                            ->nullable()
                            ->after('warranty_start'),
                    ]),

                    Textarea::make('notes')
                        ->label(__('inventory.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serial_code')
                    ->label(__('inventory.serial_code'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable()
                    ->copyMessage(__('common.copied')),

                TextColumn::make('product.name')
                    ->label(__('product.label'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('common.status'))
                    ->badge()
                    ->formatStateUsing(fn (SerialNumberStatus $state): string => $state->label())
                    ->color(fn (SerialNumberStatus $state): string => match ($state) {
                        SerialNumberStatus::Available => 'success',
                        SerialNumberStatus::Reserved => 'warning',
                        SerialNumberStatus::Sold => 'info',
                        SerialNumberStatus::Returned => 'gray',
                        SerialNumberStatus::Defective => 'danger',
                    }),

                TextColumn::make('currentLocation.name')
                    ->label(__('inventory.current_location'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('warranty_end')
                    ->label(__('inventory.warranty_end'))
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('soldToPartner.name')
                    ->label(__('inventory.sold_to'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sold_at')
                    ->label(__('inventory.sold_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->label(__('product.label'))
                    ->multiple()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('common.status'))
                    ->options([
                        SerialNumberStatus::Available->value => __('inventory.serial_status_available'),
                        SerialNumberStatus::Reserved->value => __('inventory.serial_status_reserved'),
                        SerialNumberStatus::Sold->value => __('inventory.serial_status_sold'),
                        SerialNumberStatus::Returned->value => __('inventory.serial_status_returned'),
                        SerialNumberStatus::Defective->value => __('inventory.serial_status_defective'),
                    ])
                    ->multiple(),

                SelectFilter::make('current_location_id')
                    ->relationship('currentLocation', 'name')
                    ->label(__('inventory.current_location'))
                    ->multiple()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSerialNumbers::route('/'),
            'create' => CreateSerialNumber::route('/create'),
            'edit' => EditSerialNumber::route('/{record}/edit'),
        ];
    }
}
