<?php

namespace App\Filament\Clusters\Accounting\Resources\Assets;

use App\Enums\Assets\DepreciationMethod;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\Assets\Pages\CreateAsset;
use App\Filament\Clusters\Accounting\Resources\Assets\Pages\EditAsset;
use App\Filament\Clusters\Accounting\Resources\Assets\Pages\ListAssets;
use App\Filament\Clusters\Accounting\Resources\Assets\RelationManagers\DepreciationEntryRelationManager;
use App\Filament\Resources\AssetResource\Pages;
use App\Filament\Resources\AssetResource\RelationManagers;
use App\Filament\Support\TranslatableSelect;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Asset;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = AccountingCluster::class;

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
