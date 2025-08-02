<?php

namespace App\Filament\Resources;

use App\DataTransferObjects\Assets\CreateAssetDTO;
use App\DataTransferObjects\Assets\UpdateAssetDTO;
use App\Enums\Assets\DepreciationMethod;
use App\Filament\Resources\AssetResource\Pages;
use App\Filament\Resources\AssetResource\RelationManagers;
use App\Models\Account;
use App\Models\Asset;
use App\Services\AssetService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Accounting';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('purchase_date')
                    ->required(),
                TextInput::make('purchase_value')
                    ->required()
                    ->numeric(),
                TextInput::make('salvage_value')
                    ->required()
                    ->numeric(),
                TextInput::make('useful_life_years')
                    ->required()
                    ->integer(),
                Select::make('depreciation_method')
                    ->options(DepreciationMethod::class)
                    ->required(),
                Select::make('asset_account_id')
                    ->relationship('assetAccount', 'name')
                    ->required(),
                Select::make('depreciation_expense_account_id')
                    ->relationship('depreciationExpenseAccount', 'name')
                    ->required(),
                Select::make('accumulated_depreciation_account_id')
                    ->relationship('accumulatedDepreciationAccount', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('status'),
                TextColumn::make('purchase_date')->date(),
                TextColumn::make('purchase_value')->money('IQD'), // Assuming IQD, adjust as needed
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
            RelationManagers\DepreciationEntryRelationManager::class,
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
