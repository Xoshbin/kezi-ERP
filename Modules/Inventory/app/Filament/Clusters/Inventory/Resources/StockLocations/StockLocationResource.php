<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockLocations;

use App\Enums\Inventory\StockLocationType;
use App\Filament\Clusters\Inventory\InventoryCluster;
use App\Filament\Clusters\Inventory\Resources\StockLocations\Pages\CreateStockLocation;
use App\Filament\Clusters\Inventory\Resources\StockLocations\Pages\EditStockLocation;
use App\Filament\Clusters\Inventory\Resources\StockLocations\Pages\ListStockLocations;
use App\Filament\Clusters\Inventory\Resources\StockLocations\Pages\ViewStockLocation;
use App\Models\StockLocation;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StockLocationResource extends Resource
{
    protected static ?string $model = StockLocation::class;

    protected static ?string $cluster = InventoryCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('stock_location.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('stock_location.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('stock_location.plural_label');
    }

    public static function form(Schema $schema): Schema
    {

        return $schema->components([
            Section::make(__('stock_location.basic_information'))
                ->description(__('stock_location.basic_information_description'))
                ->icon('heroicon-o-building-storefront')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label(__('stock_location.name'))
                            ->required()
                            ->maxLength(255),
                    ]),
                    Grid::make(2)->schema([
                        Select::make('type')
                            ->label(__('stock_location.type'))
                            ->required()
                            ->options(
                                collect(StockLocationType::cases())
                                    ->mapWithKeys(fn (StockLocationType $type) => [$type->value => $type->label()])
                            )
                            ->searchable(),
                        Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->label(__('stock_location.parent'))
                            ->searchable()
                            ->preload()
                            ->helperText(__('stock_location.parent_help')),
                    ]),
                ]),

            Section::make(__('stock_location.status'))
                ->description(__('stock_location.status_description'))
                ->icon('heroicon-o-check-circle')
                ->schema([
                    Toggle::make('is_active')
                        ->label(__('stock_location.is_active'))
                        ->default(true)
                        ->helperText(__('stock_location.is_active_help')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('stock_location.company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label(__('stock_location.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('type')
                    ->label(__('stock_location.type'))
                    ->badge()
                    ->formatStateUsing(fn (StockLocationType $state): string => $state->label())
                    ->color(fn (StockLocationType $state): string => match ($state) {
                        StockLocationType::Internal => 'primary',
                        StockLocationType::Customer => 'success',
                        StockLocationType::Vendor => 'warning',
                        StockLocationType::InventoryAdjustment => 'info',
                    }),
                TextColumn::make('parent.name')
                    ->label(__('stock_location.parent'))
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('children_count')
                    ->label(__('stock_location.children_count'))
                    ->counts('children')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('stock_location.is_active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('created_at')
                    ->label(__('stock_location.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('stock_location.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('stock_location.company'))
                    ->multiple()
                    ->preload(),
                SelectFilter::make('type')
                    ->label(__('stock_location.type'))
                    ->options(
                        collect(StockLocationType::cases())
                            ->mapWithKeys(fn (StockLocationType $type) => [$type->value => $type->label()])
                    )
                    ->multiple(),
                TernaryFilter::make('is_active')
                    ->label(__('stock_location.is_active'))
                    ->placeholder(__('stock_location.all_locations'))
                    ->trueLabel(__('stock_location.active_locations'))
                    ->falseLabel(__('stock_location.inactive_locations')),
                SelectFilter::make('parent_id')
                    ->relationship('parent', 'name')
                    ->label(__('stock_location.parent'))
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
            'index' => ListStockLocations::route('/'),
            'create' => CreateStockLocation::route('/create'),
            'view' => ViewStockLocation::route('/{record}'),
            'edit' => EditStockLocation::route('/{record}/edit'),
        ];
    }
}
