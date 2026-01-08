<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills;

use App\Models\Company;
use BackedEnum;
use Brick\Money\Money;
use Exception;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Enums\Accounting\TaxType;
use Modules\Accounting\Enums\Assets\DepreciationMethod;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\ListVendorBills;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\RelationManagers\AdjustmentDocumentsRelationManager;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\RelationManagers\PaymentsRelationManager;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AssetCategory;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\Tax;
use Modules\Accounting\Rules\NotInLockedPeriod;
use Modules\Foundation\Enums\Incoterm;
use Modules\Foundation\Filament\Forms\Components\MoneyInput;
use Modules\Foundation\Filament\Tables\Columns\MoneyColumn;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Modules\Payment\Actions\Payments\CreatePaymentAction;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Services\PaymentService;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class VendorBillResource extends Resource
{
    protected static ?string $model = VendorBill::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.sales_purchases');
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
            Section::make(__('accounting::bill.vendor_currency_info'))
                ->description(__('accounting::bill.vendor_currency_info_description'))
                ->schema([
                    TranslatableSelect::make('vendor_id')
                        ->relationship('vendor', 'name')
                        ->label(__('accounting::bill.vendor'))
                        ->searchableFields(['name', 'email', 'contact_person'])
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(2)
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label(__('partner.name'))
                                ->required()
                                ->maxLength(255),
                            Select::make('type')
                                ->label(__('partner.type'))
                                ->required()
                                ->options(
                                    collect(\Modules\Foundation\Enums\Partners\PartnerType::cases())
                                        ->mapWithKeys(fn (\Modules\Foundation\Enums\Partners\PartnerType $type) => [$type->value => $type->label()])
                                ),
                            TextInput::make('contact_person')
                                ->label(__('partner.contact_person'))
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label(__('partner.email'))
                                ->email()
                                ->maxLength(255),
                            TextInput::make('phone')
                                ->label(__('partner.phone'))
                                ->maxLength(255),
                            Textarea::make('address')
                                ->label(__('partner.address'))
                                ->columnSpanFull(),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_partner'))
                        ->createOptionAction(function (Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),
                    TranslatableSelect::forModel('currency_id', Currency::class, 'name')
                        ->label(__('accounting::bill.currency'))
                        ->required()
                        ->live()
                        ->searchable()
                        ->preload()
                        ->columnSpan(1)
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
                                    // Get latest exchange rate for this company
                                    $latestRate = CurrencyRate::getLatestRate($currency->id, $company->id);
                                    if ($latestRate) {
                                        $set('exchange_rate_at_creation', $latestRate);
                                    }
                                } else {
                                    $set('exchange_rate_at_creation', 1.0);
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
                            Toggle::make('is_active')
                                ->label(__('currency.is_active'))
                                ->required()
                                ->default(true),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_currency'))
                        ->createOptionAction(function (Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),
                    TextInput::make('exchange_rate_at_creation')
                        ->label(__('accounting::bill.exchange_rate_at_creation'))
                        ->numeric()
                        ->columnSpan(1)
                        ->visible(function (callable $get) {
                            $currencyId = $get('currency_id');
                            $company = Filament::getTenant();

                            return $currencyId && $company instanceof Company && $currencyId != $company->currency_id;
                        })
                        ->disabled(fn (?VendorBill $record) => $record && $record->status !== VendorBillStatus::Draft)
                        ->helperText(function (callable $get) {
                            $currencyId = $get('currency_id');
                            $company = Filament::getTenant();
                            if ($currencyId && $company instanceof Company && $currencyId !== $company->currency_id) {
                                $currency = Currency::find($currencyId);
                                if ($currency) {
                                    $latestRate = CurrencyRate::getLatestRate($currency->id, $company->id);
                                    if ($latestRate) {
                                        return __('accounting::bill.exchange_rate_helper').' '.__('accounting::bill.current_rate', ['rate' => $latestRate]);
                                    }
                                }
                            }

                            return __('accounting::bill.exchange_rate_helper');
                        }),
                    Select::make('incoterm')
                        ->label(__('accounting::bill.incoterm'))
                        ->options(Incoterm::class)
                        ->searchable()
                        ->preload(),
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
                            TableColumn::make(__('accounting::bill.product'))->width('18%'),
                            TableColumn::make(__('accounting::bill.description'))->width('12%'),
                            TableColumn::make(__('accounting::bill.quantity'))->width('8%'),
                            TableColumn::make(__('accounting::bill.unit_price'))->width('12%'),
                            TableColumn::make(__('accounting::bill.expense_account'))->width('18%'),
                            TableColumn::make(__('accounting::bill.tax'))->width('18%'),
                            TableColumn::make(__('accounting::asset.category'))->width('18%'),
                        ])
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->disabled(fn (?VendorBill $record) => $record ? $record->status !== VendorBillStatus::Draft : false)
                        ->deletable(fn (?VendorBill $record) => $record === null || $record->status === VendorBillStatus::Draft)
                        ->schema([
                            TranslatableSelect::forModel('product_id', Product::class, 'name')
                                ->label(__('accounting::bill.product'))
                                ->searchableFields(['name', 'sku', 'description'])
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $product = Product::find($state);
                                        // Ensure we have a single Product model, not a collection
                                        if ($product instanceof Collection) {
                                            $product = $product->first();
                                        }
                                        if ($product) {
                                            $set('description', $product->name);
                                            // Convert Money object to string for MoneyInput component
                                            $unitPrice = $product->unit_price;
                                            if ($unitPrice instanceof Money) {
                                                $set('unit_price', $unitPrice->getAmount()->__toString());
                                            } else {
                                                $set('unit_price', $unitPrice);
                                            }
                                            $set('expense_account_id', $product->expense_account_id);
                                        }
                                    }
                                })
                                ->createOptionForm([
                                    Select::make('company_id')
                                        ->relationship('company', 'name')
                                        ->label(__('product.company'))
                                        ->required(),
                                    TextInput::make('name')
                                        ->label(__('product.name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('sku')
                                        ->label(__('product.sku'))
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('type')
                                        ->label(__('product.type'))
                                        ->required()
                                        ->live()
                                        ->options(
                                            collect(\Modules\Product\Enums\Products\ProductType::cases())
                                                ->mapWithKeys(fn (\Modules\Product\Enums\Products\ProductType $type) => [$type->value => $type->label()])
                                        ),
                                    Textarea::make('description')
                                        ->label(__('product.description'))
                                        ->rows(3),
                                    Toggle::make('is_active')
                                        ->label(__('product.is_active'))
                                        ->default(true),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_product'))
                                ->createOptionAction(function (Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(3),
                            TextInput::make('description')
                                ->label(__('accounting::bill.description'))
                                ->maxLength(255)
                                ->required()
                                ->columnSpan(4),
                            TextInput::make('quantity')
                                ->label(__('accounting::bill.quantity'))
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->columnSpan(2),
                            MoneyInput::make('unit_price')
                                ->label(__('accounting::bill.unit_price'))
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(3),
                            TranslatableSelect::forModel('expense_account_id', Account::class, 'name')
                                ->label(__('accounting::bill.expense_account'))
                                ->searchableFields(['name', 'code'])
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(3),
                            TranslatableSelect::forModel('tax_id', Tax::class, 'name')
                                ->label(__('accounting::bill.tax'))
                                ->options(function () {
                                    return Tax::where('company_id', Filament::getTenant()?->getKey())
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    Hidden::make('company_id')
                                        ->default(fn () => Filament::getTenant()?->getKey()),
                                    Select::make('tax_account_id')
                                        ->options(function () {
                                            return Account::where('company_id', Filament::getTenant()?->getKey())
                                                ->where('is_deprecated', false)
                                                ->pluck('name', 'id');
                                        })
                                        ->label(__('accounting::tax.tax_account'))
                                        ->searchable()
                                        ->required(),
                                    TextInput::make('name')
                                        ->label(__('accounting::tax.name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('rate')
                                        ->label(__('accounting::tax.rate'))
                                        ->required()
                                        ->numeric(),
                                    Select::make('type')
                                        ->label(__('accounting::tax.type'))
                                        ->options(collect(TaxType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                                        ->required(),
                                    Toggle::make('is_active')
                                        ->label(__('accounting::tax.is_active'))
                                        ->default(true),
                                ])
                                ->createOptionUsing(function (array $data): int {
                                    $tax = Tax::create($data);

                                    return $tax->getKey();
                                })
                                ->createOptionModalHeading(__('common.modal_title_create_tax'))
                                ->createOptionAction(function (Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(3),
                            TranslatableSelect::forModel('asset_category_id', AssetCategory::class, 'name')
                                ->label(__('accounting::asset.category'))
                                ->searchableFields(['name'])
                                ->searchable()
                                ->preload()
                                ->visible(fn ($get) => $get('product_id') === null) // for service/asset purchases without product
                                ->createOptionForm([
                                    Select::make('company_id')
                                        ->relationship('company', 'name')
                                        ->label(__('accounting::asset.company'))
                                        ->required(),
                                    TextInput::make('name')
                                        ->label(__('accounting::asset.category_name'))
                                        ->required(),
                                    Select::make('asset_account_id')
                                        ->relationship('assetAccount', 'name')
                                        ->label(__('accounting::asset.asset_account'))
                                        ->required(),
                                    Select::make('accumulated_depreciation_account_id')
                                        ->relationship('accumulatedDepreciationAccount', 'name')
                                        ->label(__('accounting::asset.accumulated_depreciation_account'))
                                        ->required(),
                                    Select::make('depreciation_expense_account_id')
                                        ->relationship('depreciationExpenseAccount', 'name')
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
                                    TextInput::make('salvage_value_default')
                                        ->numeric()
                                        ->label(__('accounting::asset.salvage_value_default'))
                                        ->default(0),
                                ])
                                ->createOptionModalHeading(__('accounting::asset.create_category'))
                                ->createOptionAction(fn (Action $action) => $action->modalWidth('lg'))
                                ->columnSpan(3),
                        ])
                        ->columns(18),
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

            Section::make(__('accounting::bill.company_currency_totals'))
                ->schema([
                    TextInput::make('exchange_rate_at_creation')
                        ->label(__('accounting::bill.exchange_rate_at_creation'))
                        ->numeric()
                        ->disabled()
                        ->visible(fn (?VendorBill $record) => $record && $record->exchange_rate_at_creation),

                    MoneyInput::make('total_amount_company_currency')
                        ->label(__('accounting::bill.total_amount_company_currency'))
                        ->currencyField('../../company.currency_id')
                        ->disabled()
                        ->visible(fn (?VendorBill $record) => $record && $record->total_amount_company_currency),

                    MoneyInput::make('total_tax_company_currency')
                        ->label(__('accounting::bill.total_tax_company_currency'))
                        ->currencyField('../../company.currency_id')
                        ->disabled()
                        ->visible(fn (?VendorBill $record) => $record && $record->total_tax_company_currency),
                ])
                ->columnSpanFull()
                ->visible(fn (?VendorBill $record) => $record && ($record->exchange_rate_at_creation || $record->total_amount_company_currency)),
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
                        ? route('filament.jmeryar.purchases.resources.purchase-orders.view', [
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
                    ->formatStateUsing(fn (\Modules\Foundation\Enums\Shared\PaymentState $state): string => $state->label())
                    ->badge()
                    ->color(fn (\Modules\Foundation\Enums\Shared\PaymentState $state): string => $state->color()),

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
            ->recordActions([
                ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    EditAction::make(),
                ]),
                Action::make('register_payment')
                    ->label(__('Register Payment'))
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->modalHeading(__('Register Payment'))
                    ->modalDescription(__('Register a payment for this vendor bill'))
                    ->schema([
                        Select::make('journal_id')
                            ->label(__('payment.form.journal_id'))
                            ->options(function (): array {
                                $tenant = Filament::getTenant();
                                if (! $tenant instanceof Company) {
                                    return [];
                                }

                                return Journal::where('company_id', $tenant->getKey())
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->required()
                            ->default(function (): ?int {
                                $tenant = Filament::getTenant();
                                if (! $tenant instanceof Company) {
                                    return null;
                                }

                                return Journal::where('company_id', $tenant->getKey())
                                    ->where('type', 'bank')
                                    ->value('id');
                            }),
                        DatePicker::make('payment_date')
                            ->label(__('payment.form.payment_date'))
                            ->default(now())
                            ->required(),
                        MoneyInput::make('amount')
                            ->label(__('payment.form.amount'))
                            ->currencyField('currency_id')
                            ->default(fn (VendorBill $record) => $record->getRemainingAmount())
                            ->required(),
                        TextInput::make('reference')
                            ->label(__('payment.form.reference'))
                            ->placeholder(__('Optional reference')),
                        Hidden::make('currency_id')
                            ->default(fn (VendorBill $record) => $record->currency_id),
                    ])
                    ->action(function (VendorBill $record, array $data) {
                        try {
                            $currency = $record->currency;

                            // Create payment document link DTO
                            $documentLink = new CreatePaymentDocumentLinkDTO(
                                document_type: 'vendor_bill',
                                document_id: $record->getKey(),
                                amount_applied: Money::of($data['amount'], $currency->code)
                            );

                            // Create payment DTO
                            $paymentDTO = new CreatePaymentDTO(
                                company_id: $record->company_id,
                                journal_id: $data['journal_id'],
                                currency_id: $record->currency_id,
                                payment_date: $data['payment_date'],
                                // settlement inferred by presence of document links
                                payment_type: PaymentType::Outbound,
                                payment_method: PaymentMethod::BankTransfer,
                                paid_to_from_partner_id: $record->vendor_id,
                                amount: Money::of($data['amount'], $currency->code),
                                document_links: [$documentLink],
                                reference: $data['reference']
                            );

                            // Create and confirm payment
                            $user = Auth::user();
                            if (! $user) {
                                throw new Exception('User must be authenticated to create payment');
                            }
                            $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $user);
                            app(PaymentService::class)->confirm($payment, $user);

                            Notification::make()
                                ->title(__('Payment registered successfully'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('Error registering payment'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(
                        fn (VendorBill $record) => $record->status === VendorBillStatus::Posted &&
                        ! $record->getRemainingAmount()->isZero()
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
