<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills;

use App\Models\Company;
use BackedEnum;
use Brick\Money\Money;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\ListVendorBills;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\RelationManagers\AdjustmentDocumentsRelationManager;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\RelationManagers\PaymentsRelationManager;
use Kezi\Accounting\Filament\Forms\Components\AccountSelectField;
use Kezi\Accounting\Filament\Forms\Components\TaxSelectField;
use Kezi\Accounting\Models\AssetCategory;
use Kezi\Accounting\Rules\NotInLockedPeriod;
use Kezi\Foundation\Enums\Incoterm;
use Kezi\Foundation\Filament\Forms\Components\ExchangeRateInput;
use Kezi\Foundation\Filament\Forms\Components\MoneyInput;
use Kezi\Foundation\Filament\Forms\Components\PartnerSelectField;
use Kezi\Foundation\Filament\Helpers\DocumentTotalsHelper;
use Kezi\Foundation\Filament\Tables\Columns\MoneyColumn;
use Kezi\Foundation\Models\Currency;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Product\Filament\Forms\Components\ProductSelectField;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class VendorBillResource extends Resource
{
    protected static ?string $model = VendorBill::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.transactions');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::bill.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::bill.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::bill.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Forms\Components\Placeholder::make('shipping_warnings')
                ->columnSpanFull()
                ->hidden(fn (?VendorBill $record) => ! $record || ! $record->incoterm)
                ->content(function (?VendorBill $record) {
                    if (! $record || ! $record->incoterm) {
                        return null;
                    }

                    // We use the service to get validation results
                    $result = app(\Kezi\Purchase\Services\VendorBillService::class)->validateShippingCosts($record);

                    if ($result->isValid()) {
                        return null;
                    }

                    $warnings = collect($result->warnings)
                        ->map(fn ($warning) => '<li>'.e($warning).'</li>')
                        ->implode('');

                    return new \Illuminate\Support\HtmlString("
                        <div class=\"p-4 bg-danger-500/10 border border-danger-500/20 rounded-lg\">
                            <h4 class=\"text-danger-700 font-bold mb-2 flex items-center\">
                                <svg class=\"w-5 h-5 mr-2\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path fill-rule=\"evenodd\" d=\"M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z\" clip-rule=\"evenodd\"/></svg>
                                Shipping Cost Responsibility Warnings
                            </h4>
                            <ul class=\"list-disc list-inside text-danger-600\">
                                {$warnings}
                            </ul>
                            <p class=\"mt-2 text-xs text-danger-500\">
                                According to Incoterm {$record->incoterm->getLabel()}, these costs are typically the responsabilidad of the seller.
                            </p>
                        </div>
                    ");
                }),
            Section::make(__('accounting::bill.vendor_currency_info'))
                ->description(__('accounting::bill.vendor_currency_info_description'))
                ->schema([
                    PartnerSelectField::make('vendor_id')
                        ->label(__('accounting::bill.vendor'))
                        ->required()
                        ->columnSpan(1),
                    \Kezi\Foundation\Filament\Forms\Components\CurrencySelectField::make('currency_id')
                        ->label(__('accounting::bill.currency'))
                        ->required()
                        ->columnSpan(1),
                    ExchangeRateInput::make('exchange_rate_at_creation')
                        ->columnSpan(1)
                        ->disabled(fn (?VendorBill $record) => $record && $record->status !== VendorBillStatus::Draft),
                    Select::make('incoterm')
                        ->label(__('accounting::bill.incoterm'))
                        ->options(Incoterm::class)
                        ->searchable()
                        ->preload(),
                    TextInput::make('incoterm_location')
                        ->label(__('accounting::bill.incoterm_location'))
                        ->maxLength(255),
                    Select::make('fiscal_position_id')
                        ->label(__('accounting::bill.fiscal_position'))
                        ->relationship('fiscalPosition', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText(__('accounting::bill.fiscal_position_helper')),
                    Hidden::make('purchase_order_id'),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('accounting::bill.bill_details'))
                ->description(__('accounting::bill.bill_details_description'))
                ->schema([
                    TextInput::make('bill_reference')
                        ->label(__('accounting::bill.bill_reference'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    DatePicker::make('bill_date')
                        ->label(__('accounting::bill.bill_date'))
                        ->default(now())
                        ->required()
                        ->rules([new NotInLockedPeriod])
                        ->columnSpan(1),
                    DatePicker::make('accounting_date')
                        ->default(now())
                        ->label(__('accounting::bill.accounting_date'))
                        ->required()
                        ->rules([new NotInLockedPeriod])
                        ->columnSpan(1),
                    DatePicker::make('due_date')
                        ->label(__('accounting::bill.due_date'))
                        ->columnSpan(1),
                    TranslatableSelect::make('payment_term_id')
                        ->relationship('paymentTerm', 'name')
                        ->label(__('accounting::bill.payment_term'))
                        ->searchable()
                        ->preload()
                        ->columnSpan(1),
                ])
                ->columns(4)
                ->columnSpanFull(),
            Section::make(__('accounting::bill.line_items'))
                ->description(__('accounting::bill.line_items_description'))
                ->schema([
                    Repeater::make('lines')
                        ->label(__('accounting::bill.lines'))
                        ->table([
                            TableColumn::make(__('accounting::bill.product'))->width('20%'),
                            TableColumn::make(__('accounting::bill.description'))->width('20%'),
                            TableColumn::make(__('accounting::bill.quantity'))->width('10%'),
                            TableColumn::make(__('accounting::bill.unit_price'))->width('15%'),
                            TableColumn::make(__('accounting::bill.expense_account'))->width('20%'),
                            TableColumn::make(__('accounting::bill.tax'))->width('15%'),
                        ])
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->disabled(fn (?VendorBill $record) => $record ? $record->status !== VendorBillStatus::Draft : false)
                        ->deletable(fn (?VendorBill $record) => $record === null || $record->status === VendorBillStatus::Draft)
                        ->extraItemActions([
                            \Filament\Actions\Action::make('advanced_settings')
                                ->label(__('Advanced Settings'))
                                ->icon('heroicon-m-cog-6-tooth')
                                ->color('gray')
                                ->slideOver()
                                ->form([
                                    Section::make(__('accounting::bill.deferred_accounting'))
                                        ->description(__('accounting::bill.deferred_accounting_description'))
                                        ->schema([
                                            DatePicker::make('deferred_start_date')
                                                ->label(__('accounting::bill.deferred_start_date')),
                                            DatePicker::make('deferred_end_date')
                                                ->label(__('accounting::bill.deferred_end_date')),
                                        ])
                                        ->columns(2),
                                    Section::make(__('accounting::bill.shipping_assets'))
                                        ->schema([
                                            Select::make('shipping_cost_type')
                                                ->label(__('Shipping Type'))
                                                ->options(\Kezi\Foundation\Enums\ShippingCostType::class)
                                                ->placeholder(__('None'))
                                                ->nullable(),
                                            TranslatableSelect::forModel('asset_category_id', AssetCategory::class, 'name')
                                                ->label(__('accounting::asset.category'))
                                                ->searchableFields(['name'])
                                                ->searchable()
                                                ->preload()
                                                ->createOptionForm([
                                                    Select::make('company_id')
                                                        ->relationship('company', 'name')
                                                        ->label(__('accounting::asset.company'))
                                                        ->required(),
                                                    TextInput::make('name')
                                                        ->label(__('accounting::asset.category_name'))
                                                        ->required(),
                                                    AccountSelectField::make('asset_account_id')
                                                        ->label(__('accounting::asset.asset_account'))
                                                        ->required(),
                                                    AccountSelectField::make('accumulated_depreciation_account_id')
                                                        ->label(__('accounting::asset.accumulated_depreciation_account'))
                                                        ->required(),
                                                    AccountSelectField::make('depreciation_expense_account_id')
                                                        ->label(__('accounting::asset.depreciation_expense_account'))
                                                        ->required(),
                                                    Select::make('depreciation_method')
                                                        ->options(collect(DepreciationMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]))
                                                        ->label(__('accounting::asset.depreciation_method'))
                                                        ->required(),
                                                    TextInput::make('useful_life_years')
                                                        ->numeric()
                                                        ->label(__('accounting::asset.useful_life_years'))
                                                        ->required(),
                                                    Toggle::make('prorata_temporis')
                                                        ->label(__('accounting::asset.prorata_temporis'))
                                                        ->default(false),
                                                    TextInput::make('declining_factor')
                                                        ->label(__('accounting::asset.declining_factor'))
                                                        ->numeric()
                                                        ->visible(fn ($get) => $get('depreciation_method') === DepreciationMethod::Declining->value)
                                                        ->required(fn ($get) => $get('depreciation_method') === DepreciationMethod::Declining->value)
                                                        ->default(2.0),
                                                    TextInput::make('salvage_value_default')
                                                        ->numeric()
                                                        ->label(__('accounting::asset.salvage_value_default'))
                                                        ->default(0),
                                                ])
                                                ->createOptionModalHeading(__('accounting::asset.create_category'))
                                                ->createOptionAction(fn (Action $action) => $action->modalWidth('lg'))
                                                ->createOptionUsing(fn (array $data) => \Kezi\Accounting\Models\AssetCategory::create($data)->getKey()),
                                        ])
                                        ->columns(2),
                                ])
                                ->fillForm(fn (Repeater $component, array $arguments) => $component->getRawItemState($arguments['item']))
                                ->action(function (array $data, Repeater $component, array $arguments) {
                                    $item = $arguments['item'];
                                    $state = $component->getState();
                                    $state[$item] = array_merge($state[$item], $data);
                                    $component->state($state);
                                }),
                        ])
                        ->schema([
                            ProductSelectField::make('product_id')
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                    if ($state) {
                                        $product = Product::find($state);
                                        // Ensure we have a single Product model, not a collection
                                        if ($product instanceof Collection) {
                                            $product = $product->first();
                                        }
                                        if ($product) {
                                            $set('description', $product->description ?: $product->name);
                                            // Convert Money object to string for MoneyInput component
                                            $unitPrice = $product->unit_price;
                                            if ($unitPrice instanceof Money) {
                                                $set('unit_price', $unitPrice->getAmount()->__toString());
                                            } else {
                                                $set('unit_price', $unitPrice);
                                            }
                                            $set('expense_account_id', $product->expense_account_id);

                                            // Auto-detect shipping cost type
                                            $name = strtolower($product->name);
                                            if (str_contains($name, 'freight') || str_contains($name, 'shipping')) {
                                                $set('shipping_cost_type', \Kezi\Foundation\Enums\ShippingCostType::Freight);
                                            } elseif (str_contains($name, 'insurance')) {
                                                $set('shipping_cost_type', \Kezi\Foundation\Enums\ShippingCostType::Insurance);
                                            }
                                        }
                                    }
                                })
                                ->columnSpan(3),
                            TextInput::make('description')
                                ->label(__('accounting::bill.description'))
                                ->maxLength(255)
                                ->required(),
                            TextInput::make('quantity')
                                ->label(__('accounting::bill.quantity'))
                                ->required()
                                ->numeric()
                                ->default(1),
                            MoneyInput::make('unit_price')
                                ->label(__('accounting::bill.unit_price'))
                                ->currencyField('../../currency_id')
                                ->required(),
                            AccountSelectField::make('expense_account_id')
                                ->label(__('accounting::bill.expense_account'))
                                ->required(),
                            TaxSelectField::make('tax_id')
                                ->label(__('accounting::bill.tax')),

                            // Hidden fields to store advanced settings so they are persisted and saved
                            Hidden::make('deferred_start_date'),
                            Hidden::make('deferred_end_date'),
                            Hidden::make('shipping_cost_type'),
                            Hidden::make('asset_category_id'),
                        ]),
                ])->columnSpanFull(),
            Section::make(__('accounting::bill.attachments'))
                ->description(__('accounting::bill.attachments_description'))
                ->schema([
                    FileUpload::make('attachments')
                        ->label(__('accounting::bill.attachments'))
                        ->multiple()
                        ->disk('local')
                        ->directory('vendor-bill-attachments')
                        ->visibility('private')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'text/plain',
                        ])
                        ->maxSize(10240) // 10MB max file size
                        ->maxFiles(10)
                        ->disabled(fn (?VendorBill $record) => $record ? $record->status !== VendorBillStatus::Draft : false)
                        ->helperText(__('accounting::bill.attachments_helper'))
                        ->downloadable()
                        ->openable()
                        ->deletable(fn (?VendorBill $record) => $record === null || $record->status === VendorBillStatus::Draft)
                        ->reorderable(),
                ])
                ->collapsible()
                ->columnSpanFull()
                ->collapsed(fn (?VendorBill $record) => $record && $record->attachments()->count() === 0),

            DocumentTotalsHelper::make(
                linesKey: 'lines',
                translationPrefix: 'accounting::bill'
            ),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::bill.vendor_currency_info'))
                    ->description(__('accounting::bill.vendor_currency_info_description'))
                    ->schema([
                        TextEntry::make('vendor.name')
                            ->label(__('accounting::bill.vendor'))
                            ->weight('bold')
                            ->columnSpan(1),
                        TextEntry::make('currency.name')
                            ->label(__('accounting::bill.currency'))
                            ->columnSpan(1),
                        TextEntry::make('exchange_rate_at_creation')
                            ->label(__('accounting::bill.exchange_rate'))
                            ->numeric(decimalPlaces: 6)
                            ->columnSpan(1)
                            ->visible(fn (?VendorBill $record) => $record && $record->exchange_rate_at_creation),
                        TextEntry::make('incoterm')
                            ->label(__('accounting::bill.incoterm'))
                            ->formatStateUsing(fn (?Incoterm $state): ?string => $state?->getLabel()),
                        TextEntry::make('incoterm_location')
                            ->label(__('accounting::bill.incoterm_location')),
                        TextEntry::make('fiscalPosition.name')
                            ->label(__('accounting::bill.fiscal_position')),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make(__('accounting::bill.bill_details'))
                    ->description(__('accounting::bill.bill_details_description'))
                    ->schema([
                        TextEntry::make('bill_reference')
                            ->label(__('accounting::bill.bill_reference'))
                            ->columnSpan(1),
                        TextEntry::make('bill_date')
                            ->label(__('accounting::bill.bill_date'))
                            ->date()
                            ->columnSpan(1),
                        TextEntry::make('accounting_date')
                            ->label(__('accounting::bill.accounting_date'))
                            ->date()
                            ->columnSpan(1),
                        TextEntry::make('due_date')
                            ->label(__('accounting::bill.due_date'))
                            ->date()
                            ->columnSpan(1),
                        TextEntry::make('paymentTerm.name')
                            ->label(__('accounting::bill.payment_term'))
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make(__('accounting::bill.line_items'))
                    ->description(__('accounting::bill.line_items_description'))
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->label(__('accounting::bill.lines'))
                            ->schema([
                                Grid::make(6)
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label(__('accounting::bill.product'))
                                            ->weight('bold'),
                                        TextEntry::make('description')
                                            ->label(__('accounting::bill.description')),
                                        TextEntry::make('quantity')
                                            ->label(__('accounting::bill.quantity'))
                                            ->numeric(decimalPlaces: 2),
                                        TextEntry::make('unit_price')
                                            ->label(__('accounting::bill.unit_price'))
                                            ->money(fn ($record) => $record->vendorBill->currency->code),
                                        TextEntry::make('expenseAccount.name')
                                            ->label(__('accounting::bill.expense_account')),
                                        TextEntry::make('tax.name')
                                            ->label(__('accounting::bill.tax'))
                                            ->placeholder('—'),
                                    ]),
                            ])
                            ->contained(false)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                DocumentTotalsHelper::makeInfolist(
                    translationPrefix: 'accounting::bill',
                    subtotalKey: 'subtotal',
                    taxKey: 'total_tax',
                    totalKey: 'total_amount',
                    subtotalCompanyKey: 'subtotal_company_currency',
                    taxCompanyKey: 'total_tax_company_currency',
                    totalCompanyKey: 'total_amount_company_currency',
                    exchangeRateKey: 'exchange_rate_at_creation'
                ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Most important: Reference number (always visible)
                TextColumn::make('reference')
                    ->label(__('accounting::bill.reference'))
                    ->searchable(['bill_reference'])
                    ->getStateUsing(function (VendorBill $record): string {
                        if ($record->bill_reference) {
                            return $record->bill_reference;
                        }

                        return 'DRAFT-'.str_pad((string) $record->id, 5, '0', STR_PAD_LEFT);
                    })
                    ->badge()
                    ->color(fn (VendorBill $record): string => $record->bill_reference ? 'success' : 'warning')
                    ->icon(fn (VendorBill $record): string => $record->bill_reference ? 'heroicon-m-check-circle' : 'heroicon-m-pencil-square')
                    ->sortable(),

                // Vendor (critical for identification)
                TextColumn::make('vendor.name')
                    ->label(__('accounting::bill.vendor'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Purchase Order Reference (important for audit trail)
                TextColumn::make('purchaseOrder.po_number')
                    ->label(__('accounting::bill.purchase_order'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-document-text')
                    ->url(
                        fn (?VendorBill $record): ?string => $record?->purchaseOrder
                        ? route('filament.kezi.purchases.resources.purchase-orders.view', [
                            'record' => $record->purchaseOrder,
                            'tenant' => Filament::getTenant(),
                        ])
                        : null
                    )
                    ->openUrlInNewTab()
                    ->placeholder(__('accounting::bill.no_purchase_order'))
                    ->toggleable(),

                // Status (critical for workflow)
                TextColumn::make('status')
                    ->badge()
                    ->label(__('accounting::bill.status'))
                    ->colors([
                        'success' => VendorBillStatus::Posted,
                        'danger' => VendorBillStatus::Cancelled,
                        'warning' => VendorBillStatus::Draft,
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => VendorBillStatus::Posted,
                        'heroicon-m-x-circle' => VendorBillStatus::Cancelled,
                        'heroicon-m-pencil-square' => VendorBillStatus::Draft,
                    ])
                    ->searchable()
                    ->sortable(),

                // Bill Date (important for chronological sorting)
                TextColumn::make('bill_date')
                    ->label(__('accounting::bill.date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                // Due Date (critical for cash flow management)
                TextColumn::make('due_date')
                    ->label(__('accounting::bill.due_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                // Payment Terms
                TextColumn::make('paymentTerm.name')
                    ->label(__('accounting::bill.payment_term'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // Payment State (critical for cash flow)
                TextColumn::make('paymentState')
                    ->label(__('accounting::bill.payment_state'))
                    ->formatStateUsing(fn (\Kezi\Foundation\Enums\Shared\PaymentState $state): string => $state->label())
                    ->badge()
                    ->color(fn (\Kezi\Foundation\Enums\Shared\PaymentState $state): string => $state->color()),

                // Total Amount (critical financial information)
                MoneyColumn::make('total_amount')
                    ->label(__('accounting::bill.total'))
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                // Currency (important for multi-currency)
                TextColumn::make('currency.code')
                    ->label(__('accounting::bill.currency'))
                    ->badge()
                    ->toggleable(),

                // Company (for multi-company setups)
                TextColumn::make('company.name')
                    ->label(__('accounting::bill.company'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('exchange_rate_at_creation')
                    ->label(__('accounting::bill.exchange_rate'))
                    ->numeric(decimalPlaces: 6)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->exchange_rate_at_creation),

                MoneyColumn::make('total_amount_company_currency')
                    ->label(__('accounting::bill.total_amount_company_currency'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->total_amount_company_currency),

                // Post Date (important for audit trail)
                TextColumn::make('posted_at')
                    ->label(__('accounting::bill.posted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                // Additional columns (hidden by default for cleaner view)
                MoneyColumn::make('total_tax')
                    ->label(__('accounting::bill.tax'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('accounting::bill.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('accounting::bill.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    EditAction::make(),
                    Action::make('create_landed_cost')
                        ->label(__('Create Landed Cost'))
                        ->icon('heroicon-o-truck')
                        ->visible(fn (VendorBill $record) => $record->status === VendorBillStatus::Posted)
                        ->url(fn (VendorBill $record) => \Kezi\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource::getUrl('create', [
                            'vendor_bill_id' => $record->id,
                        ])),
                ]),
                \Kezi\Accounting\Filament\Actions\RegisterPaymentAction::make()
                    ->documentType('vendor_bill')
                    ->paymentType(PaymentType::Outbound)
                    ->partnerId(fn (VendorBill $record) => $record->vendor_id)
                    ->visible(
                        fn (VendorBill $record) => $record->status === VendorBillStatus::Posted &&
                        ! $record->getRemainingAmount()->isZero()
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\VendorBillLinesRelationManager::class,
            PaymentsRelationManager::class,
            AdjustmentDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorBills::route('/'),
            'create' => CreateVendorBill::route('/create'),
            'edit' => EditVendorBill::route('/{record}/edit'),
            'view' => Pages\ViewVendorBill::route('/{record}'),
        ];
    }
}
