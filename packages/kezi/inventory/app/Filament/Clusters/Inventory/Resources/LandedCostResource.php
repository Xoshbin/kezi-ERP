<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Kezi\Inventory\Enums\Inventory\LandedCostAllocationMethod;
use Kezi\Inventory\Enums\Inventory\LandedCostStatus;
use Kezi\Inventory\Filament\Clusters\Inventory\InventoryCluster;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource\Pages;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource\RelationManagers;
use Kezi\Inventory\Models\LandedCost;

class LandedCostResource extends Resource
{
    protected static ?string $model = LandedCost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $cluster = InventoryCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Operations');
    }

    public static function getModelLabel(): string
    {
        return __('inventory::landed_cost.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inventory::landed_cost.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory::landed_cost.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('inventory::landed_cost.section_details'))
                    ->schema([
                        Select::make('vendor_bill_id')
                            ->relationship('vendorBill', 'bill_reference')
                            ->searchable()
                            ->preload()
                            ->label(__('inventory::landed_cost.fields.vendor_bill')),

                        DatePicker::make('date')
                            ->label(__('inventory::landed_cost.fields.date'))
                            ->required()
                            ->default(now()),

                        TextInput::make('amount_total')
                            ->required()
                            ->rule('numeric')
                            ->formatStateUsing(fn ($state) => $state instanceof \Brick\Money\Money ? $state->getAmount()->toFloat() : $state)
                            ->label(__('inventory::landed_cost.fields.total_amount')),

                        Hidden::make('company_id')
                            ->default(fn () => \Filament\Facades\Filament::getTenant()->id),

                        Select::make('allocation_method')
                            ->label(__('inventory::landed_cost.fields.allocation_method'))
                            ->options(LandedCostAllocationMethod::class)
                            ->required()
                            ->default(LandedCostAllocationMethod::ByQuantity),

                        TextInput::make('description')
                            ->label(__('inventory::landed_cost.fields.description'))
                            ->maxLength(255),

                        Select::make('status')
                            ->label(__('inventory::landed_cost.fields.status'))
                            ->options(LandedCostStatus::class)
                            ->required()
                            ->default(LandedCostStatus::Draft)
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('inventory::landed_cost.fields.id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('inventory::landed_cost.fields.date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendorBill.bill_reference')
                    ->label(__('inventory::landed_cost.fields.vendor_bill'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount_total')
                    ->label(__('inventory::landed_cost.fields.total_amount'))
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('allocation_method')
                    ->label(__('inventory::landed_cost.fields.allocation_method'))
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('inventory::landed_cost.fields.status'))
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StockPickingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLandedCosts::route('/'),
            'create' => Pages\CreateLandedCost::route('/create'),
            'edit' => Pages\EditLandedCost::route('/{record}/edit'),
        ];
    }
}
