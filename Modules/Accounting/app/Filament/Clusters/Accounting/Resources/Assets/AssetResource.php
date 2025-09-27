<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets;

use App\Enums\Accounting\AccountType;
use App\Enums\Assets\AssetStatus;
use App\Enums\Assets\DepreciationMethod;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\Assets\Pages\CreateAsset;
use App\Filament\Clusters\Accounting\Resources\Assets\Pages\EditAsset;
use App\Filament\Clusters\Accounting\Resources\Assets\Pages\ListAssets;
use App\Filament\Clusters\Accounting\Resources\Assets\RelationManagers\DepreciationEntryRelationManager;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Rules\NotInLockedPeriod;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

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
        return $schema->components([
            Section::make(__('asset.asset_currency_info'))
                ->description(__('asset.asset_currency_info_description'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('asset.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    TranslatableSelect::forModel('currency_id', Currency::class)
                        ->label(__('asset.currency'))
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
                        ->label(__('asset.current_exchange_rate'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(function (callable $get) {
                            $currencyId = $get('currency_id');
                            $company = Filament::getTenant();

                            return $currencyId && $company instanceof Company && $currencyId != $company->currency_id;
                        })
                        ->helperText(__('asset.exchange_rate_helper')),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('asset.asset_details'))
                ->description(__('asset.asset_details_description'))
                ->schema([
                    DatePicker::make('purchase_date')
                        ->label(__('asset.purchase_date'))
                        ->default(now())
                        ->required()
                        ->rules([new NotInLockedPeriod])
                        ->columnSpan(1),

                    MoneyInput::make('purchase_value')
                        ->label(__('asset.purchase_value'))
                        ->currencyField('../../company.currency_id')
                        ->required()
                        ->columnSpan(1),

                    MoneyInput::make('salvage_value')
                        ->label(__('asset.salvage_value'))
                        ->currencyField('../../company.currency_id')
                        ->default(0)
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('useful_life_years')
                        ->label(__('asset.useful_life_years'))
                        ->required()
                        ->numeric()
                        ->columnSpan(1),

                    Select::make('depreciation_method')
                        ->label(__('asset.depreciation_method'))
                        ->searchable()
                        ->options(
                            collect(DepreciationMethod::cases())
                                ->mapWithKeys(fn (DepreciationMethod $method) => [$method->value => $method->label()])
                        )
                        ->required()
                        ->columnSpan(1),

                    TranslatableSelect::forModel('asset_account_id', Account::class)
                        ->label(__('asset.asset_account'))
                        ->searchableFields(['name', 'code'])
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Select::make('company_id')
                                ->relationship('company', 'name')
                                ->label(__('account.company'))
                                ->required(),
                            TextInput::make('code')
                                ->label(__('account.code'))
                                ->required(),
                            TextInput::make('name')
                                ->label(__('account.name'))
                                ->required(),
                            Select::make('type')
                                ->label(__('account.type'))
                                ->options(collect(AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                                ->default(AccountType::FixedAssets->value)
                                ->required(),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(fn (Action $action) => $action->modalWidth('lg'))
                        ->required()
                        ->columnSpan(1),

                    TranslatableSelect::forModel('depreciation_expense_account_id', Account::class)
                        ->label(__('asset.depreciation_expense_account'))
                        ->searchableFields(['name', 'code'])
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Select::make('company_id')
                                ->relationship('company', 'name')
                                ->label(__('account.company'))
                                ->required(),
                            TextInput::make('code')
                                ->label(__('account.code'))
                                ->required(),
                            TextInput::make('name')
                                ->label(__('account.name'))
                                ->required(),
                            Select::make('type')
                                ->label(__('account.type'))
                                ->options(collect(AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                                ->default(AccountType::Depreciation->value)
                                ->required(),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(fn (Action $action) => $action->modalWidth('lg'))
                        ->required()
                        ->columnSpan(1),

                    TranslatableSelect::forModel('accumulated_depreciation_account_id', Account::class)
                        ->label(__('asset.accumulated_depreciation_account'))
                        ->searchableFields(['name', 'code'])
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Select::make('company_id')
                                ->relationship('company', 'name')
                                ->label(__('account.company'))
                                ->required(),
                            TextInput::make('code')
                                ->label(__('account.code'))
                                ->required(),
                            TextInput::make('name')
                                ->label(__('account.name'))
                                ->required(),
                            Select::make('type')
                                ->label(__('account.type'))
                                ->options(collect(AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                                ->default(AccountType::FixedAssets->value)
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
                    ->label(__('asset.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                TextColumn::make('status')
                    ->label(__('asset.status'))
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
                    ->label(__('asset.purchase_date'))
                    ->date()
                    ->sortable(),

                MoneyColumn::make('purchase_value')
                    ->label(__('asset.purchase_value'))
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                TextColumn::make('depreciation_method')
                    ->label(__('asset.depreciation_method'))
                    ->formatStateUsing(fn (DepreciationMethod $state): string => $state->label())
                    ->badge()
                    ->toggleable(),

                TextColumn::make('useful_life_years')
                    ->label(__('asset.useful_life'))
                    ->suffix(' '.__('asset.years'))
                    ->toggleable(),

                TextColumn::make('currency.code')
                    ->label(__('asset.currency'))
                    ->badge()
                    ->toggleable(),

                TextColumn::make('company.name')
                    ->label(__('asset.company'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('asset.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('asset.updated_at'))
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
