<?php

namespace Modules\Inventory\Filament\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Inventory\Enums\Inventory\LandedCostAllocationMethod;
use Modules\Inventory\Enums\Inventory\LandedCostStatus;
use Modules\Inventory\Filament\Resources\LandedCostResource\Pages;
use Modules\Inventory\Filament\Resources\LandedCostResource\RelationManagers;
use Modules\Inventory\Models\LandedCost;

class LandedCostResource extends Resource
{
    protected static ?string $model = LandedCost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    public static function getNavigationGroup(): ?string
    {
        return 'Operations';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('vendor_bill_id')
                            ->relationship('vendorBill', 'bill_reference')
                            ->searchable()
                            ->preload()
                            ->label('Vendor Bill'),

                        DatePicker::make('date')
                            ->required()
                            ->default(now()),

                        TextInput::make('amount_total')
                            ->required()
                            ->numeric()
                            ->label('Total Amount'),

                        Select::make('allocation_method')
                            ->options(LandedCostAllocationMethod::class)
                            ->required()
                            ->default(LandedCostAllocationMethod::ByQuantity),

                        TextInput::make('description')
                            ->maxLength(255),

                        Select::make('status')
                            ->options(LandedCostStatus::class)
                            ->required()
                            ->default(LandedCostStatus::Draft)
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendorBill.bill_reference')
                    ->label('Vendor Bill')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount_total')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('allocation_method')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
