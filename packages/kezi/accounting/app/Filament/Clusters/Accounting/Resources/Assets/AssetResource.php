<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Assets\AssetStatus;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages\CreateAsset;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages\EditAsset;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages\ListAssets;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\RelationManagers\DepreciationEntryRelationManager;
use Kezi\Accounting\Filament\Forms\Components\AccountSelectField;
use Kezi\Accounting\Models\Asset;
use Kezi\Accounting\Rules\NotInLockedPeriod;
use Kezi\Foundation\Filament\Forms\Components\ExchangeRateInput;
use Kezi\Foundation\Filament\Helpers\DocumentAttachmentsHelper;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.accounting_settings');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::asset.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::asset.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::asset.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('accounting::asset.asset_currency_info'))
                ->description(__('accounting::asset.asset_currency_info_description'))
                ->schema([
                    Hidden::make('company_id')
                        ->default(fn () => Filament::getTenant()?->getKey())
                        ->dehydrated(),

                    TextInput::make('name')
                        ->label(__('accounting::asset.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    \Kezi\Foundation\Filament\Forms\Components\CurrencySelectField::make('currency_id')
                        ->label(__('accounting::asset.currency'))
                        ->exchangeRateFieldName('current_exchange_rate')
                        ->required(),

                    ExchangeRateInput::make('current_exchange_rate')
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('accounting::asset.asset_details'))
                ->description(__('accounting::asset.asset_details_description'))
                ->schema([
                    DatePicker::make('purchase_date')
                        ->label(__('accounting::asset.purchase_date'))
                        ->default(now())
                        ->required()
                        ->rules([new NotInLockedPeriod])
                        ->columnSpan(1),

                    \Kezi\Foundation\Filament\Forms\Components\MoneyInput::make('purchase_value')
                        ->label(__('accounting::asset.purchase_value'))
                        ->currencyField('../../company.currency_id')
                        ->required()
                        ->columnSpan(1),

                    \Kezi\Foundation\Filament\Forms\Components\MoneyInput::make('salvage_value')
                        ->label(__('accounting::asset.salvage_value'))
                        ->currencyField('../../company.currency_id')
                        ->default(0)
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('useful_life_years')
                        ->label(__('accounting::asset.useful_life_years'))
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->step(1)
                        ->columnSpan(1),

                    \Filament\Forms\Components\Toggle::make('prorata_temporis')
                        ->label(__('accounting::asset.prorata_temporis'))
                        ->default(false)
                        ->inline(false)
                        ->columnSpan(1),

                    Select::make('depreciation_method')
                        ->label(__('accounting::asset.depreciation_method'))
                        ->searchable()
                        ->live()
                        ->options(
                            collect(DepreciationMethod::cases())
                                ->mapWithKeys(fn (DepreciationMethod $method) => [$method->value => $method->label()])
                        )
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('declining_factor')
                        ->label(__('accounting::asset.declining_factor'))
                        ->required(fn ($get) => $get('depreciation_method') === DepreciationMethod::Declining->value)
                        ->visible(fn ($get) => $get('depreciation_method') === DepreciationMethod::Declining->value)
                        ->numeric()
                        ->minValue(1)
                        ->default(2.0)
                        ->step(0.1)
                        ->columnSpan(1),

                    AccountSelectField::make('asset_account_id')
                        ->label(__('accounting::asset.asset_account'))
                        ->required()
                        ->createOptionDefaultType(AccountType::FixedAssets)
                        ->columnSpan(1),

                    AccountSelectField::make('depreciation_expense_account_id')
                        ->label(__('accounting::asset.depreciation_expense_account'))
                        ->required()
                        ->createOptionDefaultType(AccountType::Depreciation)
                        ->columnSpan(1),

                    AccountSelectField::make('accumulated_depreciation_account_id')
                        ->label(__('accounting::asset.accumulated_depreciation_account'))
                        ->required()
                        ->createOptionDefaultType(AccountType::FixedAssets)
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->columnSpanFull(),

            DocumentAttachmentsHelper::makeSection(
                directory: 'assets',
                disabledCallback: fn (?Asset $record) => $record && $record->status !== AssetStatus::Draft,
                deletableCallback: fn (?Asset $record) => $record === null || $record->status === AssetStatus::Draft
            ),
        ]);
    }

    /**
     * @return Builder<Asset>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id)
            ->with(['company.currency', 'currency', 'assetAccount', 'depreciationExpenseAccount', 'accumulatedDepreciationAccount']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('accounting::asset.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                TextColumn::make('status')
                    ->label(__('accounting::asset.status'))
                    ->badge()
                    ->formatStateUsing(fn (AssetStatus $state): string => $state->label())
                    ->colors([
                        'gray' => AssetStatus::Draft,
                        'info' => AssetStatus::Confirmed,
                        'warning' => AssetStatus::Depreciating,
                        'success' => AssetStatus::FullyDepreciated,
                        'danger' => AssetStatus::Sold,
                    ])
                    ->sortable(),

                TextColumn::make('purchase_date')
                    ->label(__('accounting::asset.purchase_date'))
                    ->date()
                    ->sortable(),

                \Kezi\Foundation\Filament\Tables\Columns\MoneyColumn::make('purchase_value')
                    ->label(__('accounting::asset.purchase_value'))
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                TextColumn::make('depreciation_method')
                    ->label(__('accounting::asset.depreciation_method'))
                    ->formatStateUsing(fn (DepreciationMethod $state): string => $state->label())
                    ->badge()
                    ->toggleable(),

                TextColumn::make('useful_life_years')
                    ->label(__('accounting::asset.useful_life'))
                    ->suffix(' '.__('accounting::asset.years'))
                    ->toggleable(),

                TextColumn::make('currency.code')
                    ->label(__('accounting::asset.currency'))
                    ->badge()
                    ->toggleable(),

                TextColumn::make('company.name')
                    ->label(__('accounting::asset.company'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('accounting::asset.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('accounting::asset.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
