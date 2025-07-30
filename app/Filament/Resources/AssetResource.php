<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetResource\Pages;
use App\Filament\Resources\AssetResource\RelationManagers;
use App\Models\Asset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('asset.company'))
                    ->required(),
                Forms\Components\Select::make('asset_account_id')
                    ->relationship('assetAccount', 'name')
                    ->label(__('asset.asset_account'))
                    ->required(),
                Forms\Components\Select::make('depreciation_expense_account_id')
                    ->relationship('depreciationExpenseAccount', 'name')
                    ->label(__('asset.depreciation_expense_account'))
                    ->required(),
                Forms\Components\Select::make('accumulated_depreciation_account_id')
                    ->relationship('accumulatedDepreciationAccount', 'name')
                    ->label(__('asset.accumulated_depreciation_account'))
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label(__('asset.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('purchase_date')
                    ->label(__('asset.purchase_date'))
                    ->required(),
                Forms\Components\TextInput::make('purchase_value')
                    ->label(__('asset.purchase_value'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('salvage_value')
                    ->label(__('asset.salvage_value'))
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('useful_life_years')
                    ->label(__('asset.useful_life_years'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('depreciation_method')
                    ->label(__('asset.depreciation_method'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->label(__('asset.status'))
                    ->required()
                    ->maxLength(255)
                    ->default('Draft'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('asset.company'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assetAccount.name')
                    ->label(__('asset.asset_account'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('depreciationExpenseAccount.name')
                    ->label(__('asset.depreciation_expense_account'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('accumulatedDepreciationAccount.name')
                    ->label(__('asset.accumulated_depreciation_account'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('asset.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label(__('asset.purchase_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_value')
                    ->label(__('asset.purchase_value'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salvage_value')
                    ->label(__('asset.salvage_value'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('useful_life_years')
                    ->label(__('asset.useful_life_years'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('depreciation_method')
                    ->label(__('asset.depreciation_method'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('asset.status'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('asset.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('asset.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            RelationManagers\DepreciationEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
