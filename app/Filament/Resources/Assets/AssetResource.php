<?php

namespace App\Filament\Resources\Assets;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Assets\RelationManagers\DepreciationEntryRelationManager;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\Assets\Pages\CreateAsset;
use App\Filament\Resources\Assets\Pages\EditAsset;
use Filament\Forms;
use Filament\Tables;
use App\Models\Asset;
use App\Models\Account;
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
use App\Filament\Support\TranslatableSelect;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.core_accounting');
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TranslatableSelect::withFormatter(
                    'asset_account_id',
                    \App\Models\Account::class,
                    fn($account) => [$account->id => $account->getTranslatedLabel('name') . ' (' . $account->code . ')'],
                    __('asset.asset_account')
                )
                    ->required(),
                TranslatableSelect::withFormatter(
                    'depreciation_expense_account_id',
                    \App\Models\Account::class,
                    fn($account) => [$account->id => $account->getTranslatedLabel('name') . ' (' . $account->code . ')'],
                    __('asset.depreciation_expense_account')
                )
                    ->required(),
                TranslatableSelect::withFormatter(
                    'accumulated_depreciation_account_id',
                    \App\Models\Account::class,
                    fn($account) => [$account->id => $account->getTranslatedLabel('name') . ' (' . $account->code . ')'],
                    __('asset.accumulated_depreciation_account')
                )
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DepreciationEntryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'edit' => EditAsset::route('/{record}/edit'),
        ];
    }
}
