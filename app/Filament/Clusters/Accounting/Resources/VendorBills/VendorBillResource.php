<?php

namespace App\Filament\Clusters\Accounting\Resources\VendorBills;

use App\Actions\Payments\CreatePaymentAction;
use App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Enums\Accounting\TaxType;
use App\Enums\Accounting\AccountType;
use App\Enums\Assets\DepreciationMethod;
use App\Enums\Partners\PartnerType;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentType;
use App\Enums\Products\ProductType;
use App\Enums\Purchases\VendorBillStatus;
use App\Enums\Shared\PaymentState;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\ListVendorBills;
use App\Filament\Clusters\Accounting\Resources\VendorBills\RelationManagers\AdjustmentDocumentsRelationManager;
use App\Filament\Clusters\Accounting\Resources\VendorBills\RelationManagers\PaymentsRelationManager;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Account;
use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Journal;
use App\Models\Product;
use App\Models\Tax;
use App\Models\VendorBill;
use App\Rules\NotInLockedPeriod;
use App\Services\PaymentService;
use BackedEnum;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
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
        return __('vendor_bill.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('vendor_bill.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('vendor_bill.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('vendor_bill.vendor_currency_info'))
                ->description(__('vendor_bill.vendor_currency_info_description'))
                ->schema([
                    TranslatableSelect::make('vendor_id')
                        ->relationship('vendor', 'name')
                        ->label(__('vendor_bill.vendor'))
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
                                    collect(PartnerType::cases())
                                        ->mapWithKeys(fn (PartnerType $type) => [$type->value => $type->label()])
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
                        ->label(__('vendor_bill.currency'))
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
                    TextInput::make('current_exchange_rate')
                        ->label(__('vendor_bill.current_exchange_rate'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1)
                        ->visible(function (callable $get) {
                            $currencyId = $get('currency_id');
                            $company = Filament::getTenant();

                            return $currencyId && $company instanceof Company && $currencyId != $company->currency_id;
                        })
                        ->helperText(__('vendor_bill.exchange_rate_helper')),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('vendor_bill.bill_details'))
                ->description(__('vendor_bill.bill_details_description'))
                ->schema([
                    TextInput::make('bill_reference')
                        ->label(__('vendor_bill.bill_reference'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    DatePicker::make('bill_date')
                        ->label(__('vendor_bill.bill_date'))
                        ->default(now())
                        ->required()
                        ->rules([new NotInLockedPeriod])
                        ->columnSpan(1),
                    DatePicker::make('accounting_date')
                        ->default(now())
                        ->label(__('vendor_bill.accounting_date'))
                        ->required()
                        ->rules([new NotInLockedPeriod])
                        ->columnSpan(1),
                    DatePicker::make('due_date')
                        ->label(__('vendor_bill.due_date'))
                        ->columnSpan(1),
                    TranslatableSelect::make('payment_term_id')
                        ->relationship('paymentTerm', 'name')
                        ->label(__('vendor_bill.payment_term'))
                        ->searchable()
                        ->preload()
                        ->columnSpan(1),
                ])
                ->columns(4)
                ->columnSpanFull(),
            Section::make(__('vendor_bill.line_items'))
                ->description(__('vendor_bill.line_items_description'))
                ->schema([
                    Repeater::make('lines')
                        ->label(__('vendor_bill.lines'))
                        ->table([
                            TableColumn::make(__('vendor_bill.product'))->width('18%'),
                            TableColumn::make(__('vendor_bill.description'))->width('12%'),
                            TableColumn::make(__('vendor_bill.quantity'))->width('8%'),
                            TableColumn::make(__('vendor_bill.unit_price'))->width('12%'),
                            TableColumn::make(__('vendor_bill.expense_account'))->width('18%'),
                            TableColumn::make(__('vendor_bill.tax'))->width('18%'),
                            TableColumn::make(__('asset.category'))->width('18%'),
                        ])
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->disabled(fn (?VendorBill $record) => $record ? $record->status !== VendorBillStatus::Draft : false)
                        ->deletable(fn (?VendorBill $record) => $record === null || $record->status === VendorBillStatus::Draft)
                        ->schema([
                            TranslatableSelect::forModel('product_id', Product::class, 'name')
                                ->label(__('vendor_bill.product'))
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
                                            if ($unitPrice instanceof \Brick\Money\Money) {
                                                $set('unit_price', $unitPrice->getAmount()->__toString());
                                            } else {
                                                $set('unit_price', $unitPrice);
                                            }
                                            $set('expense_account_id', $product->expense_account_id);
                                        }
                                    }
                                })
                                ->createOptionForm([
                                    Hidden::make('company_id')
                                        ->default(fn () => Filament::getTenant()?->getKey()),
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
                                            collect(ProductType::cases())
                                                ->mapWithKeys(fn (ProductType $type) => [$type->value => $type->label()])
                                        ),
                                    Textarea::make('description')
                                        ->label(__('product.description'))
                                        ->rows(3),
                                    TranslatableSelect::make('default_inventory_account_id')
                                        ->relationship('inventoryAccount', 'name')
                                        ->label(__('product.default_inventory_account'))
                                        ->searchable()
                                        ->preload()
                                        ->searchableFields(['name'])
                                        ->visible(fn ($get) => $get('type') === ProductType::Storable->value)
                                        ->required(fn ($get) => $get('type') === ProductType::Storable->value)
                                        ->rules(['required_if:type,'.ProductType::Storable->value])
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
                                                    collect(AccountType::cases())
                                                        ->mapWithKeys(fn (AccountType $type) => [$type->value => $type->label()])
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
                                ->label(__('vendor_bill.description'))
                                ->maxLength(255)
                                ->required()
                                ->columnSpan(4),
                            TextInput::make('quantity')
                                ->label(__('vendor_bill.quantity'))
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->columnSpan(2),
                            MoneyInput::make('unit_price')
                                ->label(__('vendor_bill.unit_price'))
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(3),
                            TranslatableSelect::forModel('expense_account_id', Account::class, 'name')
                                ->label(__('vendor_bill.expense_account'))
                                ->searchableFields(['name', 'code'])
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(3),
                            TranslatableSelect::forModel('tax_id', Tax::class, 'name')
                                ->label(__('vendor_bill.tax'))
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    Select::make('company_id')
                                        ->relationship('company', 'name')
                                        ->label(__('tax.company'))
                                        ->required(),
                                    Select::make('tax_account_id')
                                        ->relationship('taxAccount', 'name')
                                        ->label(__('tax.tax_account'))
                                        ->required(),
                                    TextInput::make('name')
                                        ->label(__('tax.name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('rate')
                                        ->label(__('tax.rate'))
                                        ->required()
                                        ->numeric(),
                                    Select::make('type')
                                        ->label(__('tax.type'))
                                        ->options(collect(TaxType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                                        ->required(),
                                    Toggle::make('is_active')
                                        ->label(__('tax.is_active'))
                                        ->default(true),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_tax'))
                                ->createOptionAction(function (Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(3),
                            TranslatableSelect::forModel('asset_category_id', AssetCategory::class, 'name')
                                ->label(__('asset.category'))
                                ->searchableFields(['name'])
                                ->searchable()
                                ->preload()
                                ->visible(fn ($get) => $get('product_id') === null) // for service/asset purchases without product
                                ->createOptionForm([
                                    Select::make('company_id')
                                        ->relationship('company', 'name')
                                        ->label(__('asset.company'))
                                        ->required(),
                                    TextInput::make('name')
                                        ->label(__('asset.category_name'))
                                        ->required(),
                                    Select::make('asset_account_id')
                                        ->relationship('assetAccount', 'name')
                                        ->label(__('asset.asset_account'))
                                        ->required(),
                                    Select::make('accumulated_depreciation_account_id')
                                        ->relationship('accumulatedDepreciationAccount', 'name')
                                        ->label(__('asset.accumulated_depreciation_account'))
                                        ->required(),
                                    Select::make('depreciation_expense_account_id')
                                        ->relationship('depreciationExpenseAccount', 'name')
                                        ->label(__('asset.depreciation_expense_account'))
                                        ->required(),
                                    Select::make('depreciation_method')
                                        ->options(collect(DepreciationMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]))
                                        ->label(__('asset.depreciation_method'))
                                        ->required(),
                                    TextInput::make('useful_life_years')
                                        ->numeric()
                                        ->label(__('asset.useful_life_years'))
                                        ->required(),
                                    TextInput::make('salvage_value_default')
                                        ->numeric()
                                        ->label(__('asset.salvage_value_default'))
                                        ->default(0),
                                ])
                                ->createOptionModalHeading(__('asset.create_category'))
                                ->createOptionAction(fn (Action $action) => $action->modalWidth('lg'))
                                ->columnSpan(3),
                        ])
                        ->columns(18),
                ])->columnSpanFull(),
            Section::make(__('vendor_bill.attachments'))
                ->description(__('vendor_bill.attachments_description'))
                ->schema([
                    FileUpload::make('attachments')
                        ->label(__('vendor_bill.attachments'))
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
                        ->helperText(__('vendor_bill.attachments_helper'))
                        ->downloadable()
                        ->openable()
                        ->deletable(fn (?VendorBill $record) => $record === null || $record->status === VendorBillStatus::Draft)
                        ->reorderable(),
                ])
                ->collapsible()
                ->columnSpanFull()
                ->collapsed(fn (?VendorBill $record) => $record && $record->attachments()->count() === 0),

            Section::make(__('vendor_bill.company_currency_totals'))
                ->schema([
                    TextInput::make('exchange_rate_at_creation')
                        ->label(__('vendor_bill.exchange_rate_at_creation'))
                        ->numeric()
                        ->disabled()
                        ->visible(fn (?VendorBill $record) => $record && $record->exchange_rate_at_creation),

                    MoneyInput::make('total_amount_company_currency')
                        ->label(__('vendor_bill.total_amount_company_currency'))
                        ->currencyField('../../company.currency_id')
                        ->disabled()
                        ->visible(fn (?VendorBill $record) => $record && $record->total_amount_company_currency),

                    MoneyInput::make('total_tax_company_currency')
                        ->label(__('vendor_bill.total_tax_company_currency'))
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
                    ->label(__('vendor_bill.reference'))
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
                    ->label(__('vendor_bill.vendor'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Status (critical for workflow)
                TextColumn::make('status')
                    ->badge()
                    ->label(__('vendor_bill.status'))
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
                    ->label(__('vendor_bill.date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                // Due Date (critical for cash flow management)
                TextColumn::make('due_date')
                    ->label(__('vendor_bill.due_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                // Payment Terms
                TextColumn::make('paymentTerm.name')
                    ->label(__('vendor_bill.payment_term'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // Payment State (critical for cash flow)
                TextColumn::make('paymentState')
                    ->label(__('vendor_bill.payment_state'))
                    ->formatStateUsing(fn (PaymentState $state): string => $state->label())
                    ->badge()
                    ->color(fn (PaymentState $state): string => $state->color()),

                // Total Amount (critical financial information)
                MoneyColumn::make('total_amount')
                    ->label(__('vendor_bill.total'))
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                // Currency (important for multi-currency)
                TextColumn::make('currency.code')
                    ->label(__('vendor_bill.currency'))
                    ->badge()
                    ->toggleable(),

                // Company (for multi-company setups)
                TextColumn::make('company.name')
                    ->label(__('vendor_bill.company'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('exchange_rate_at_creation')
                    ->label(__('vendor_bill.exchange_rate'))
                    ->numeric(decimalPlaces: 6)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->exchange_rate_at_creation),

                MoneyColumn::make('total_amount_company_currency')
                    ->label(__('vendor_bill.total_amount_company_currency'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->total_amount_company_currency),

                // Posted Date (important for audit trail)
                TextColumn::make('posted_at')
                    ->label(__('vendor_bill.posted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                // Additional columns (hidden by default for cleaner view)
                MoneyColumn::make('total_tax')
                    ->label(__('vendor_bill.tax'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('vendor_bill.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('vendor_bill.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
        ];
    }
}
