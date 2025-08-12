<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Company;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Account;
use App\Enums\Products\ProductType;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Accounting\AccountType;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.inventory');
    }

    public static function getModelLabel(): string
    {
        return __('product.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('product.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('product.plural_label');
    }

    public static function form(Form $form): Form
    {
        $company = Company::first();

        return $form->schema([
            Section::make(__('product.basic_information'))
                ->description(__('product.basic_information_description'))
                ->icon('heroicon-o-cube')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->label(__('product.company'))
                            ->required()
                            ->live()
                            ->searchable()
                            ->default($company?->id)
                            ->afterStateUpdated(function (callable $set, $state) {
                                $company = Company::find($state);
                                if ($company) {
                                    $set('currency_id', $company->currency_id);
                                }
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('company.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('address')
                                    ->label(__('company.address'))
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('tax_id')
                                    ->label(__('company.tax_id'))
                                    ->maxLength(255),
                                Forms\Components\Select::make('currency_id')
                                    ->label(__('company.currency_id'))
                                    ->relationship('currency', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('fiscal_country')
                                    ->label(__('company.fiscal_country'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_company'))
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action
                                    ->modalWidth('lg');
                            }),
                        Forms\Components\TextInput::make('name')
                            ->label(__('product.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                    ]),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label(__('product.sku'))
                            ->required()
                            ->maxLength(255)
                            ->unique(Product::class, 'sku', ignoreRecord: true),
                        Forms\Components\Select::make('type')
                            ->label(__('product.type'))
                            ->required()
                            ->options(
                                collect(ProductType::cases())
                                    ->mapWithKeys(fn (ProductType $type) => [$type->value => $type->label()])
                            )
                            ->searchable(),
                    ]),
                    Forms\Components\Textarea::make('description')
                        ->label(__('product.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make(__('product.pricing_information'))
                ->description(__('product.pricing_information_description'))
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    Forms\Components\Hidden::make('currency_id'),
                    MoneyInput::make('unit_price')
                        ->label(__('product.unit_price'))
                        ->required()
                        ->currencyField('currency_id'),
                ]),

            Section::make(__('product.accounting_configuration'))
                ->description(__('product.accounting_configuration_description'))
                ->icon('heroicon-o-calculator')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('income_account_id')
                            ->relationship('incomeAccount', 'name')
                            ->label(__('product.income_account'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\Select::make('company_id')
                                    ->label(__('account.company'))
                                    ->relationship('company', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(\App\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\App\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Forms\Components\Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action
                                    ->modalWidth('lg');
                            }),
                        Forms\Components\Select::make('expense_account_id')
                            ->relationship('expenseAccount', 'name')
                            ->label(__('product.expense_account'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\Select::make('company_id')
                                    ->label(__('account.company'))
                                    ->relationship('company', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(\App\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\App\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Forms\Components\Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action
                                    ->modalWidth('lg');
                            }),
                    ]),
                ]),

            Section::make(__('product.inventory_management'))
                ->description(__('product.inventory_management_description'))
                ->icon('heroicon-o-cube-transparent')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('inventory_valuation_method')
                            ->label(__('product.inventory_valuation_method'))
                            ->options(
                                collect(ValuationMethod::cases())
                                    ->mapWithKeys(fn (ValuationMethod $method) => [$method->value => $method->label()])
                            )
                            ->searchable()
                            ->live()
                            ->visible(fn (Forms\Get $get) => $get('type') === ProductType::Storable->value)
                            ->helperText(__('product.inventory_valuation_method_help')),
                        MoneyInput::make('average_cost')
                            ->label(__('product.average_cost'))
                            ->currencyField('currency_id')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => $get('type') === ProductType::Storable->value)
                            ->helperText(__('product.average_cost_help')),
                    ]),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('default_inventory_account_id')
                            ->relationship('inventoryAccount', 'name')
                            ->label(__('product.default_inventory_account'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('type') === ProductType::Storable->value)
                            ->createOptionForm([
                                Forms\Components\Select::make('company_id')
                                    ->label(__('account.company'))
                                    ->relationship('company', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(AccountType::cases())
                                            ->mapWithKeys(fn (AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Forms\Components\Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('lg');
                            }),
                        Forms\Components\Select::make('default_cogs_account_id')
                            ->relationship('defaultCogsAccount', 'name')
                            ->label(__('product.default_cogs_account'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('type') === ProductType::Storable->value)
                            ->createOptionForm([
                                Forms\Components\Select::make('company_id')
                                    ->label(__('account.company'))
                                    ->relationship('company', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(AccountType::cases())
                                            ->mapWithKeys(fn (AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Forms\Components\Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('lg');
                            }),
                    ]),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('default_stock_input_account_id')
                            ->relationship('stockInputAccount', 'name')
                            ->label(__('product.default_stock_input_account'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('type') === ProductType::Storable->value)
                            ->createOptionForm([
                                Forms\Components\Select::make('company_id')
                                    ->label(__('account.company'))
                                    ->relationship('company', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(AccountType::cases())
                                            ->mapWithKeys(fn (AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Forms\Components\Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('lg');
                            }),
                        Forms\Components\Select::make('default_price_difference_account_id')
                            ->relationship('defaultPriceDifferenceAccount', 'name')
                            ->label(__('product.default_price_difference_account'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('type') === ProductType::Storable->value)
                            ->createOptionForm([
                                Forms\Components\Select::make('company_id')
                                    ->label(__('account.company'))
                                    ->relationship('company', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(AccountType::cases())
                                            ->mapWithKeys(fn (AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Forms\Components\Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('lg');
                            }),
                    ]),
                ])
                ->visible(fn (Forms\Get $get) => $get('type') === ProductType::Storable->value),

            Section::make(__('product.status'))
                ->description(__('product.status_description'))
                ->icon('heroicon-o-check-circle')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('product.is_active'))
                        ->default(true)
                        ->helperText(__('product.is_active_help')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('product.company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('product.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('sku')
                    ->label(__('product.sku'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('product.sku_copied'))
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('product.type'))
                    ->badge()
                    ->formatStateUsing(fn (ProductType $state): string => $state->label())
                    ->color(fn (ProductType $state): string => match ($state) {
                        ProductType::Product => 'primary',
                        ProductType::Service => 'success',
                        ProductType::Storable => 'warning',
                        ProductType::Consumable => 'info',
                    }),
                MoneyColumn::make('unit_price')
                    ->label(__('product.unit_price'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('inventory_valuation_method')
                    ->label(__('product.inventory_valuation_method'))
                    ->badge()
                    ->formatStateUsing(fn (?ValuationMethod $state): string => $state?->label() ?? '-')
                    ->color(fn (?ValuationMethod $state): string => match ($state) {
                        ValuationMethod::AVCO => 'primary',
                        ValuationMethod::FIFO => 'success',
                        ValuationMethod::LIFO => 'warning',
                        ValuationMethod::STANDARD => 'info',
                        default => 'gray',
                    })
                    ->visible(fn () => request()->has('inventory_view'))
                    ->toggleable(isToggledHiddenByDefault: true),
                MoneyColumn::make('average_cost')
                    ->label(__('product.average_cost'))
                    ->sortable()
                    ->visible(fn () => request()->has('inventory_view'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('incomeAccount.name')
                    ->label(__('product.income_account'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expenseAccount.name')
                    ->label(__('product.expense_account'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('product.is_active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('product.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('product.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('product.company'))
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('product.type'))
                    ->options(
                        collect(ProductType::cases())
                            ->mapWithKeys(fn (ProductType $type) => [$type->value => $type->label()])
                    )
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('product.is_active'))
                    ->placeholder(__('product.all_products'))
                    ->trueLabel(__('product.active_products'))
                    ->falseLabel(__('product.inactive_products')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StockMovesRelationManager::class,
            RelationManagers\InventoryCostLayersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
