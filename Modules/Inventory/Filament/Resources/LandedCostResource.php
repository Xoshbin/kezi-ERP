<?php

namespace Modules\Inventory\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Inventory\Enums\Inventory\LandedCostAllocationMethod;
use Modules\Inventory\Enums\Inventory\LandedCostStatus;
use Modules\Inventory\Filament\Resources\LandedCostResource\Pages;
use Modules\Inventory\Models\LandedCost;

class LandedCostResource extends Resource
{
    protected static ?string $model = LandedCost::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Operations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('vendor_bill_id')
                            ->relationship('vendorBill', 'bill_reference')
                            ->searchable()
                            ->preload()
                            ->label('Vendor Bill'),

                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('amount_total')
                            ->required()
                            ->numeric()
                            ->label('Total Amount'),

                        Forms\Components\Select::make('allocation_method')
                            ->options(LandedCostAllocationMethod::class)
                            ->required()
                            ->default(LandedCostAllocationMethod::ByQuantity),

                        Forms\Components\TextInput::make('description')
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
