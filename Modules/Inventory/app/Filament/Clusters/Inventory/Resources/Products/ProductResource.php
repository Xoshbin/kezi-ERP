<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\Products;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Filament\Forms\Components\MoneyInput;
use Modules\Foundation\Filament\Tables\Columns\MoneyColumn;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Inventory\Filament\Clusters\Inventory\InventoryCluster;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages\CreateProduct;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages\EditProduct;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages\ListProducts;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers\InventoryCostLayersRelationManager;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers\ReorderingRulesRelationManager;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers\StockMovesRelationManager;
use Modules\Product\Models\Product;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

// use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers\StockMovesRelationManager;

class ProductResource extends Resource
{
    use Translatable;

    protected static ?string $model = Product::class;

    protected static ?string $cluster = InventoryCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('product.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('product.plural_label');
    }

    public static function getLabel(): ?string
    {
        return __('product.label');
    }

    public static function getPluralLabel(): ?string
    {
        return __('product.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('product.label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('product.basic_information'))
                ->description(__('product.basic_information_description'))
                ->icon('heroicon-o-cube')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label(__('product.name'))
                            ->required()
                            ->maxLength(255),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('sku')
                            ->label(__('product.sku'))
                            ->required()
                            ->maxLength(255)
                            ->unique(Product::class, 'sku', ignoreRecord: true),
                        Select::make('type')
                            ->label(__('product.type'))
                            ->required()
                            ->options(
                                collect(\Modules\Product\Enums\Products\ProductType::cases())
                                    ->mapWithKeys(fn (\Modules\Product\Enums\Products\ProductType $type) => [$type->value => $type->label()])
                            )
                            ->searchable(),
                    ]),
                    Textarea::make('description')
                        ->label(__('product.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make(__('product.pricing_information'))
                ->description(__('product.pricing_information_description'))
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    Hidden::make('currency_id'),
                    MoneyInput::make('unit_price')
                        ->nullable()
                        ->label(__('product.unit_price'))
                        ->currencyField('currency_id'),
                ]),

            Section::make(__('product.accounting_configuration'))
                ->description(__('product.accounting_configuration_description'))
                ->icon('heroicon-o-calculator')
                ->schema([
                    Grid::make(2)->schema([
                        TranslatableSelect::make('income_account_id')
                            ->relationship('incomeAccount', 'name')
                            ->label(__('product.income_account'))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->searchableFields(['name', 'code'])
                            ->modifyQueryUsing(fn ($query) => $query->whereIn('type', [\Modules\Accounting\Enums\Accounting\AccountType::Income, \Modules\Accounting\Enums\Accounting\AccountType::OtherIncome]))
                            ->createOptionForm([
                                TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Modules\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Action $action) {
                                return $action
                                    ->modalWidth('lg');
                            }),
                        TranslatableSelect::make('expense_account_id')
                            ->relationship('expenseAccount', 'name')
                            ->label(__('product.expense_account'))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->searchableFields(['name', 'code'])
                            ->modifyQueryUsing(fn ($query) => $query->whereIn('type', [\Modules\Accounting\Enums\Accounting\AccountType::Expense, \Modules\Accounting\Enums\Accounting\AccountType::Depreciation, \Modules\Accounting\Enums\Accounting\AccountType::CostOfRevenue]))
                            ->createOptionForm([
                                TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Modules\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Action $action) {
                                return $action
                                    ->modalWidth('lg');
                            }),
                    ]),
                ]),

            Section::make(__('product.inventory_management'))
                ->description(__('product.inventory_management_description'))
                ->icon('heroicon-o-cube-transparent')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('inventory_valuation_method')
                            ->label(__('product.inventory_valuation_method'))
                            ->options(
                                collect(ValuationMethod::cases())
                                    ->mapWithKeys(fn (ValuationMethod $method) => [$method->value => $method->label()])
                            )
                            ->default(ValuationMethod::AVCO->value)
                            ->searchable()
                            ->live()
                            ->visible(fn (Get $get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                            ->helperText(__('product.inventory_valuation_method_help')),
                        MoneyInput::make('average_cost')
                            ->label(__('product.average_cost'))
                            ->currencyField('currency_id')
                            ->disabled()
                            ->visible(fn (Get $get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                            ->helperText(__('product.average_cost_help')),
                    ]),
                    Grid::make(2)->schema([
                        TranslatableSelect::make('default_inventory_account_id')
                            ->relationship('inventoryAccount', 'name')
                            ->label(__('product.default_inventory_account'))
                            ->searchable()
                            ->preload()
                            ->searchableFields(['name'])
                            ->required(fn (Get $get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                            ->rules(['required_if:type,'.\Modules\Product\Enums\Products\ProductType::Storable->value])
                            ->visible(fn (Get $get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                            ->createOptionForm([
                                Hidden::make('company_id')
                                    ->default(fn () => Filament::getTenant()?->getKey()),
                                TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Modules\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Action $action) {
                                return $action->modalWidth('lg');
                            }),
                        TranslatableSelect::make('default_cogs_account_id')
                            ->relationship('defaultCogsAccount', 'name')
                            ->forModel('default_cogs_account_id', Account::class, 'name')
                            ->label(__('product.default_cogs_account'))
                            ->searchable()
                            ->preload()
                            ->searchableFields(['name'])

                            ->visible(fn (Get $get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                            ->createOptionForm([
                                Hidden::make('company_id')
                                    ->default(fn () => Filament::getTenant()?->getKey()),
                                TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Modules\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Action $action) {
                                return $action->modalWidth('lg');
                            }),
                    ]),
                    Grid::make(2)->schema([
                        TranslatableSelect::make('default_stock_input_account_id')
                            ->relationship('stockInputAccount', 'name')
                            ->label(__('product.default_stock_input_account'))
                            ->searchable()
                            ->preload()
                            ->searchableFields(['name', 'code'])

                            ->visible(fn (Get $get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                            ->createOptionForm([
                                Hidden::make('company_id')
                                    ->default(fn () => Filament::getTenant()?->getKey()),
                                TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Modules\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Action $action) {
                                return $action->modalWidth('lg');
                            }),
                        TranslatableSelect::make('default_price_difference_account_id')
                            ->relationship('defaultPriceDifferenceAccount', 'name')
                            ->label(__('product.default_price_difference_account'))
                            ->searchable()
                            ->preload()
                            ->searchableFields(['name', 'code'])

                            ->visible(fn (Get $get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                            ->createOptionForm([
                                Hidden::make('company_id')
                                    ->default(fn () => Filament::getTenant()?->getKey()),
                                TextInput::make('code')
                                    ->label(__('account.code'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name')
                                    ->label(__('account.name'))
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label(__('account.type'))
                                    ->required()
                                    ->options(
                                        collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Modules\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                    )
                                    ->searchable(),
                                Toggle::make('is_deprecated')
                                    ->label(__('account.is_deprecated'))
                                    ->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(function (Action $action) {
                                return $action->modalWidth('lg');
                            }),
                    ]),
                    Grid::make(1)->schema([
                        Select::make('tracking_type')
                            ->label(__('inventory.tracking_type'))
                            ->options([
                                \Modules\Inventory\Enums\Inventory\TrackingType::None->value => __('inventory.tracking_type_none'),
                                \Modules\Inventory\Enums\Inventory\TrackingType::Lot->value => __('inventory.tracking_type_lot'),
                                \Modules\Inventory\Enums\Inventory\TrackingType::Serial->value => __('inventory.tracking_type_serial'),
                            ])
                            ->default(\Modules\Inventory\Enums\Inventory\TrackingType::None->value)
                            ->disabled(fn ($record) => $record?->hasStockMoves())
                            ->helperText(fn ($record) => $record?->hasStockMoves()
                                ? __('inventory.tracking_type_immutable_help')
                                : __('inventory.tracking_type_help'))
                            ->visible(fn (Get $get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                            ->required(fn (Get $get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value),
                    ]),
                ])
                ->visible(fn (Get $get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value),

            Section::make(__('product.status'))
                ->description(__('product.status_description'))
                ->icon('heroicon-o-check-circle')
                ->schema([
                    Toggle::make('is_active')
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
                TextColumn::make('company.name')
                    ->label(__('product.company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label(__('product.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('sku')
                    ->label(__('product.sku'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('product.sku_copied'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('type')
                    ->label(__('product.type'))
                    ->badge()
                    ->formatStateUsing(fn (\Modules\Product\Enums\Products\ProductType $state): string => $state->label())
                    ->color(fn (\Modules\Product\Enums\Products\ProductType $state): string => match ($state) {
                        \Modules\Product\Enums\Products\ProductType::Product => 'primary',
                        \Modules\Product\Enums\Products\ProductType::Service => 'success',
                        \Modules\Product\Enums\Products\ProductType::Storable => 'warning',
                        \Modules\Product\Enums\Products\ProductType::Consumable => 'info',
                    }),
                MoneyColumn::make('unit_price')
                    ->label(__('product.unit_price'))
                    ->sortable(),
                TextColumn::make('inventory_valuation_method')
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
                TextColumn::make('quantity_on_hand')
                    ->label(__('product.quantity_on_hand'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state > 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('incomeAccount.name')
                    ->label(__('product.income_account'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expenseAccount.name')
                    ->label(__('product.expense_account'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label(__('product.is_active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('created_at')
                    ->label(__('product.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('product.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('product.company'))
                    ->multiple()
                    ->preload(),
                SelectFilter::make('type')
                    ->label(__('product.type'))
                    ->options(
                        collect(\Modules\Product\Enums\Products\ProductType::cases())
                            ->mapWithKeys(fn (\Modules\Product\Enums\Products\ProductType $type) => [$type->value => $type->label()])
                    )
                    ->multiple(),
                TernaryFilter::make('is_active')
                    ->label(__('product.is_active'))
                    ->placeholder(__('product.all_products'))
                    ->trueLabel(__('product.active_products'))
                    ->falseLabel(__('product.inactive_products')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    /**
     * @return Builder<Product>
     */
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
            StockMovesRelationManager::class,
            InventoryCostLayersRelationManager::class,
            ReorderingRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
