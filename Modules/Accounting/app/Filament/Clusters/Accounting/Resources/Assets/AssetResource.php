<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets;

use App\Models\Company;
use BackedEnum;
use Filament\Actions\Action;
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
use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Enums\Assets\AssetStatus;
use Modules\Accounting\Enums\Assets\DepreciationMethod;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages\CreateAsset;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages\EditAsset;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages\ListAssets;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\RelationManagers\DepreciationEntryRelationManager;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Asset;
use Modules\Accounting\Rules\NotInLockedPeriod;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;


class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.core_accounting');
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
                        ->default(fn () => Filament::getTenant()->id)
                        ->dehydrated(),

                    TextInput::make('name')
                        ->label(__('accounting::asset.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    Select::make('currency_id')
                        ->relationship('currency', 'name')
                        ->label(__('accounting::asset.currency'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->default(function (): ?int {
                            $tenant = Filament::getTenant();

                            return $tenant instanceof Company ? $tenant->currency_id : null;
                        })
                        ->afterStateUpdated(function (callable $set, $state) {
                            if ($state) {
                                $currency = Currency::find($state);
                                // Ensure we have a single Currency model, not a collection
                                if ($currency instanceof Collection) {
                                    $currency = $currency->first();
                                }
                                $company = Filament::getTenant();

                                if ($currency && $company instanceof Company && $currency->id !== $company->currency_id) {
                                    $latestRate = CurrencyRate::getLatestRate($currency->id, $company->id);
                                    if ($latestRate) {
                                        $set('current_exchange_rate', $latestRate);
                                    }
                                } else {
                                    $set('current_exchange_rate', 1.0);
                                }
                            }
                        })
                        ->createOptionForm([
                            TextInput::make('code')
                                ->label(__('currency.code'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name')
                                ->label(__('currency.name'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('symbol')
                                ->label(__('currency.symbol'))
                                ->required()
                                ->maxLength(5),
                            TextInput::make('exchange_rate')
                                ->label(__('currency.exchange_rate'))
                                ->required()
                                ->numeric()
                                ->default(1),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_currency'))
                        ->createOptionAction(fn (Action $action) => $action->modalWidth('lg')),

                    TextInput::make('current_exchange_rate')
                        ->label(__('accounting::asset.current_exchange_rate'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(function (callable $get) {
                            $currencyId = $get('currency_id');
                            $company = Filament::getTenant();

                            return $currencyId && $company instanceof Company && $currencyId != $company->currency_id;
                        })
                        ->helperText(__('accounting::asset.exchange_rate_helper')),
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

                    \Modules\Foundation\Filament\Forms\Components\MoneyInput::make('purchase_value')
                        ->label(__('accounting::asset.purchase_value'))
                        ->currencyField('../../company.currency_id')
                        ->required()
                        ->columnSpan(1),

                    \Modules\Foundation\Filament\Forms\Components\MoneyInput::make('salvage_value')
                        ->label(__('accounting::asset.salvage_value'))
                        ->currencyField('../../company.currency_id')
                        ->default(0)
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('useful_life_years')
                        ->label(__('accounting::asset.useful_life_years'))
                        ->required()
                        ->numeric()
                        ->columnSpan(1),

                    Select::make('depreciation_method')
                        ->label(__('accounting::asset.depreciation_method'))
                        ->searchable()
                        ->options(
                            collect(DepreciationMethod::cases())
                                ->mapWithKeys(fn (DepreciationMethod $method) => [$method->value => $method->label()])
                        )
                        ->required()
                        ->columnSpan(1),

                    Select::make('asset_account_id')
                        ->relationship('assetAccount', 'name')
                        ->label(__('accounting::asset.asset_account'))
                        ->searchable(['name', 'code'])
                        ->preload()
                        ->createOptionForm([
                            Select::make('company_id')
                                ->relationship('company', 'name')
                                ->label(__('accounting::account.company'))
                                ->required(),
                            TextInput::make('code')
                                ->label(__('accounting::account.code'))
                                ->required(),
                            TextInput::make('name')
                                ->label(__('accounting::account.name'))
                                ->required(),
                            Select::make('type')
                                ->label(__('accounting::account.type'))
                                ->options(collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                                ->default(\Modules\Accounting\Enums\Accounting\AccountType::FixedAssets->value)
                                ->required(),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(fn (Action $action) => $action->modalWidth('lg'))
                        ->required()
                        ->columnSpan(1),

                    Select::make('depreciation_expense_account_id')
                        ->relationship('depreciationExpenseAccount', 'name')
                        ->label(__('accounting::asset.depreciation_expense_account'))
                        ->searchable(['name', 'code'])
                        ->preload()
                        ->createOptionForm([
                            Select::make('company_id')
                                ->relationship('company', 'name')
                                ->label(__('accounting::account.company'))
                                ->required(),
                            TextInput::make('code')
                                ->label(__('accounting::account.code'))
                                ->required(),
                            TextInput::make('name')
                                ->label(__('accounting::account.name'))
                                ->required(),
                            Select::make('type')
                                ->label(__('accounting::account.type'))
                                ->options(collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                                ->default(\Modules\Accounting\Enums\Accounting\AccountType::Depreciation->value)
                                ->required(),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(fn (Action $action) => $action->modalWidth('lg'))
                        ->required()
                        ->columnSpan(1),

                    Select::make('accumulated_depreciation_account_id')
                        ->relationship('accumulatedDepreciationAccount', 'name')
                        ->label(__('accounting::asset.accumulated_depreciation_account'))
                        ->searchable(['name', 'code'])
                        ->preload()
                        ->createOptionForm([
                            Select::make('company_id')
                                ->relationship('company', 'name')
                                ->label(__('accounting::account.company'))
                                ->required(),
                            TextInput::make('code')
                                ->label(__('accounting::account.code'))
                                ->required(),
                            TextInput::make('name')
                                ->label(__('accounting::account.name'))
                                ->required(),
                            Select::make('type')
                                ->label(__('accounting::account.type'))
                                ->options(collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                                ->default(\Modules\Accounting\Enums\Accounting\AccountType::FixedAssets->value)
                                ->required(),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(fn (Action $action) => $action->modalWidth('lg'))
                        ->required()
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return Builder<Asset>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
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

                \Modules\Foundation\Filament\Tables\Columns\MoneyColumn::make('purchase_value')
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
