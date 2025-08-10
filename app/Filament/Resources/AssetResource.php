<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Asset;
use App\Models\Account;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Services\AssetService;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Assets\DepreciationMethod;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Filament\Resources\AssetResource\Pages;
use App\DataTransferObjects\Assets\CreateAssetDTO;
use App\DataTransferObjects\Assets\UpdateAssetDTO;
use App\Filament\Resources\AssetResource\RelationManagers;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.financial_planning');
    }

    public static function getModelLabel(): string
    {
        return __('asset.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('asset.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('asset.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('asset.name'))
                    ->required()
                    ->maxLength(255),
                DatePicker::make('purchase_date')
                    ->label(__('asset.purchase_date'))
                    ->required(),
                TextInput::make('purchase_value')
                    ->label(__('asset.purchase_value'))
                    ->required()
                    ->numeric(),
                TextInput::make('salvage_value')
                    ->label(__('asset.salvage_value'))
                    ->required()
                    ->numeric(),
                TextInput::make('useful_life_years')
                    ->label(__('asset.useful_life_years'))
                    ->required()
                    ->integer(),
                Select::make('depreciation_method')
                    ->label(__('asset.depreciation_method'))
                    ->options(
                        collect(DepreciationMethod::cases())
                            ->mapWithKeys(fn (DepreciationMethod $method) => [$method->value => $method->label()])
                    )
                    ->required(),
                Select::make('asset_account_id')
                    ->label(__('asset.asset_account'))
                    ->relationship('assetAccount', 'name')
                    ->required(),
                Select::make('depreciation_expense_account_id')
                    ->label(__('asset.depreciation_expense_account'))
                    ->relationship('depreciationExpenseAccount', 'name')
                    ->required(),
                Select::make('accumulated_depreciation_account_id')
                    ->label(__('asset.accumulated_depreciation_account'))
                    ->relationship('accumulatedDepreciationAccount', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('asset.name'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('asset.status')),
                TextColumn::make('purchase_date')
                    ->label(__('asset.purchase_date'))
                    ->date(),
                MoneyColumn::make('purchase_value')
                    ->label(__('asset.purchase_value'))
                    ->sortable(),
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
