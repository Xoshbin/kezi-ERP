<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products;

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
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Filament\Forms\Components\MoneyInput;
use Kezi\Foundation\Filament\Tables\Columns\MoneyColumn;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Filament\Clusters\Inventory\InventoryCluster;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages\CreateProduct;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages\EditProduct;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages\ListProducts;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers\InventoryCostLayersRelationManager;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers\ReorderingRulesRelationManager;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers\StockMovesRelationManager;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers\VariantsRelationManager;
use Kezi\Product\Actions\GenerateProductVariantsAction;
use Kezi\Product\Models\Product;
use Kezi\Product\Models\ProductAttribute;
use Kezi\Product\Models\ProductAttributeValue;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

// use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers\StockMovesRelationManager;

class ProductResource extends Resource
{
    use Translatable;

    protected static ?string $model = Product::class;

    protected static ?string $cluster = InventoryCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('inventory::navigation.groups.products');
    }

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
                                collect(\Kezi\Product\Enums\Products\ProductType::cases())
                                    ->mapWithKeys(fn (\Kezi\Product\Enums\Products\ProductType $type) => [$type->value => $type->label()])
                            )
                            ->searchable(),
                    ]),
                    Textarea::make('description')
                        ->label(__('product.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                    Toggle::make('is_template')
                        ->label(__('product::product.is_template'))
                        ->helperText(__('product::product.is_template_help'))
                        ->live()
                        ->disabled(fn ($record) => $record?->variants()->exists()),
                ]),

            Section::make(__('product::product.variant_attributes'))
                ->description(__('product::product.variant_attributes_description'))
                ->icon('heroicon-o-variable')
                ->visible(fn (Get $get) => $get('is_template'))
                ->schema([
                    Repeater::make('product_attributes')
                        ->label(__('product::product.attributes'))
                        ->schema([
                            Select::make('product_attribute_id')
                                ->label(__('product::product.attribute'))
                                ->options(ProductAttribute::active()->pluck('name', 'id'))
                                ->required()
                                ->live(),
                            Select::make('values')
                                ->label(__('product::product.values'))
                                ->multiple()
                                ->options(fn (Get $get) => ProductAttributeValue::where('product_attribute_id', $get('product_attribute_id'))
                                    ->active()
                                    ->pluck('name', 'id')
                                )
                                ->required(),
                        ])
                        ->columns(2)
                        ->addActionLabel('Add Attribute'),
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
                            ->modifyQueryUsing(fn ($query) => $query->whereIn('type', [\Kezi\Accounting\Enums\Accounting\AccountType::Income, \Kezi\Accounting\Enums\Accounting\AccountType::OtherIncome]))
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
                                        collect(\Kezi\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Kezi\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
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
                            ->modifyQueryUsing(fn ($query) => $query->whereIn('type', [\Kezi\Accounting\Enums\Accounting\AccountType::Expense, \Kezi\Accounting\Enums\Accounting\AccountType::Depreciation, \Kezi\Accounting\Enums\Accounting\AccountType::CostOfRevenue]))
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
                                        collect(\Kezi\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Kezi\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
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
                    Grid::make(2)->schema([
                        TranslatableSelect::make('purchaseTaxes')
                            ->relationship('purchaseTaxes', 'name')
                            ->label(__('product.purchase_tax'))
                            ->multiple()
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->modifyQueryUsing(fn ($query) => $query->whereIn('type', [\Kezi\Accounting\Enums\Accounting\TaxType::Purchase, \Kezi\Accounting\Enums\Accounting\TaxType::Both])),
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
                            ->default(ValuationMethod::Avco->value)
                            ->searchable()
                            ->live()
                            ->visible(fn (Get $get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value)
                            ->helperText(__('product.inventory_valuation_method_help')),
                        MoneyInput::make('average_cost')
                            ->label(__('product.average_cost'))
                            ->currencyField('currency_id')
                            ->disabled()
                            ->visible(fn (Get $get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value)
                            ->helperText(__('product.average_cost_help')),
                    ]),
                    Grid::make(2)->schema([
                        TranslatableSelect::make('default_inventory_account_id')
                            ->relationship('inventoryAccount', 'name')
                            ->label(__('product.default_inventory_account'))
                            ->searchable()
                            ->preload()
                            ->searchableFields(['name'])
                            ->required(fn (Get $get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value)
                            ->rules(['required_if:type,'.\Kezi\Product\Enums\Products\ProductType::Storable->value])
                            ->visible(fn (Get $get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value)
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
                                        collect(\Kezi\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Kezi\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
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

                            ->visible(fn (Get $get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value)
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
                                        collect(\Kezi\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Kezi\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
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

                            ->visible(fn (Get $get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value)
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
                                        collect(\Kezi\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Kezi\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
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

                            ->visible(fn (Get $get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value)
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
                                        collect(\Kezi\Accounting\Enums\Accounting\AccountType::cases())
                                            ->mapWithKeys(fn (\Kezi\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
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
                                \Kezi\Inventory\Enums\Inventory\TrackingType::None->value => __('inventory.tracking_type_none'),
                                \Kezi\Inventory\Enums\Inventory\TrackingType::Lot->value => __('inventory.tracking_type_lot'),
                                \Kezi\Inventory\Enums\Inventory\TrackingType::Serial->value => __('inventory.tracking_type_serial'),
                            ])
                            ->default(\Kezi\Inventory\Enums\Inventory\TrackingType::None->value)
                            ->disabled(fn ($record) => $record?->hasStockMoves())
                            ->helperText(fn ($record) => $record?->hasStockMoves()
                                ? __('inventory.tracking_type_immutable_help')
                                : __('inventory.tracking_type_help'))
                            ->visible(fn (Get $get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value)
                            ->required(fn (Get $get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value),
                    ]),
                ])
                ->visible(fn (Get $get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value),

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
                    ->formatStateUsing(fn (\Kezi\Product\Enums\Products\ProductType $state): string => $state->label())
                    ->color(fn (\Kezi\Product\Enums\Products\ProductType $state): string => match ($state) {
                        \Kezi\Product\Enums\Products\ProductType::Product => 'primary',
                        \Kezi\Product\Enums\Products\ProductType::Service => 'success',
                        \Kezi\Product\Enums\Products\ProductType::Storable => 'warning',
                        \Kezi\Product\Enums\Products\ProductType::Consumable => 'info',
                    }),
                MoneyColumn::make('unit_price')
                    ->label(__('product.unit_price'))
                    ->sortable(),
                TextColumn::make('inventory_valuation_method')
                    ->label(__('product.inventory_valuation_method'))
                    ->badge()
                    ->formatStateUsing(fn (?ValuationMethod $state): string => $state?->label() ?? '-')
                    ->color(fn (?ValuationMethod $state): string => match ($state) {
                        ValuationMethod::Avco => 'primary',
                        ValuationMethod::Fifo => 'success',
                        ValuationMethod::Lifo => 'warning',
                        ValuationMethod::Standard => 'info',
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
                        collect(\Kezi\Product\Enums\Products\ProductType::cases())
                            ->mapWithKeys(fn (\Kezi\Product\Enums\Products\ProductType $type) => [$type->value => $type->label()])
                    )
                    ->multiple(),
                TernaryFilter::make('is_active')
                    ->label(__('product.is_active'))
                    ->placeholder(__('product.all_products'))
                    ->trueLabel(__('product.active_products'))
                    ->falseLabel(__('product.inactive_products')),
                TernaryFilter::make('is_template')
                    ->label(__('product::product.is_template')),
                TernaryFilter::make('is_variant')
                    ->label(__('product::product.is_variant'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('parent_product_id'),
                        false: fn (Builder $query) => $query->whereNull('parent_product_id'),
                    ),
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

    /**
     * @return array<int, Action>
     */
    public static function getActions(): array
    {
        return [
            \Filament\Actions\Action::make('generate_variants')
                ->label(__('product::product.actions.generate_variants'))
                ->color('success')
                ->icon('heroicon-o-sparkles')
                ->visible(fn (Product $record) => $record->is_template)
                ->form([
                    Wizard::make([
                        Wizard\Step::make(__('product::product.variant_generation.options'))
                            ->schema([
                                Toggle::make('delete_existing')
                                    ->label(__('product::product.delete_existing_variants'))
                                    ->helperText(__('product::product.delete_existing_variants_help'))
                                    ->default(false),
                            ]),
                        Wizard\Step::make(__('product::product.variant_generation.preview'))
                            ->schema([
                                CheckboxList::make('selected_variants')
                                    ->label(__('product::product.variant_generation.select_variants'))
                                    ->options(function (Product $record, $livewire) {
                                        $action = app(GenerateProductVariantsAction::class);
                                        $productAttributes = (isset($livewire->data['product_attributes']) && ! empty($livewire->data['product_attributes']))
                                            ? $livewire->data['product_attributes']
                                            : ($record->product_attributes ?? []);

                                        $attributeValueMap = [];
                                        foreach ($productAttributes as $attr) {
                                            $attributeValueMap[$attr['product_attribute_id']] = $attr['values'];
                                        }
                                        $previews = $action->previewCombinations($record->id, $attributeValueMap);

                                        return collect($previews)->mapWithKeys(fn ($p, $i) => [(string) $i => "{$p['sku']} ({$p['values']})"]);
                                    })
                                    ->default(function (Product $record, $livewire) {
                                        $action = app(GenerateProductVariantsAction::class);
                                        $productAttributes = (isset($livewire->data['product_attributes']) && ! empty($livewire->data['product_attributes']))
                                            ? $livewire->data['product_attributes']
                                            : ($record->product_attributes ?? []);

                                        $attributeValueMap = [];
                                        foreach ($productAttributes as $attr) {
                                            $attributeValueMap[$attr['product_attribute_id']] = $attr['values'];
                                        }
                                        $previews = $action->previewCombinations($record->id, $attributeValueMap);

                                        return array_map('strval', array_keys($previews));
                                    })
                                    ->columns(2)
                                    ->required(),
                            ]),
                    ]),
                ])
                ->action(function (\Filament\Actions\Action $action, Product $record, $livewire) {
                    $actionLogic = app(GenerateProductVariantsAction::class);

                    $attributeValueMap = [];
                    $productAttributes = (isset($livewire->data['product_attributes']) && ! empty($livewire->data['product_attributes']))
                        ? $livewire->data['product_attributes']
                        : ($record->product_attributes ?? []);

                    foreach ($productAttributes as $attr) {
                        $attributeValueMap[$attr['product_attribute_id']] = $attr['values'];
                    }

                    $allCombinations = $actionLogic->previewCombinations($record->id, $attributeValueMap);
                    $state = $action->getFormData();
                    $selectedIndices = $state['selected_variants'] ?? [];
                    $filteredCombinations = [];

                    foreach ($selectedIndices as $index) {
                        if (isset($allCombinations[(int) $index])) {
                            $filteredCombinations[] = $allCombinations[(int) $index]['combination'];
                        }
                    }

                    $actionLogic->execute(new \Kezi\Product\DataTransferObjects\GenerateProductVariantsDTO(
                        templateProductId: $record->id,
                        attributeValueMap: $attributeValueMap,
                        deleteExisting: $state['delete_existing'] ?? false,
                    ), $filteredCombinations);

                    Notification::make()
                        ->success()
                        ->title(__('product::product.actions.generate_variants_success'))
                        ->send();
                }),
        ];
    }

    public static function getRelations(): array
    {
        return [
            StockMovesRelationManager::class,
            InventoryCostLayersRelationManager::class,
            ReorderingRulesRelationManager::class,
            VariantsRelationManager::class,
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
