<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices;

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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Enums\Accounting\TaxType;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\CreateInvoice;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ListInvoices;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\RelationManagers\AdjustmentDocumentsRelationManager;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\RelationManagers\InvoiceLinesRelationManager;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\RelationManagers\PaymentsRelationManager;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPosition;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\Tax;
use Modules\Accounting\Rules\NotInLockedPeriod;
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
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Services\InvoiceService;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.sales_purchases');
    }

    public static function getModelLabel(): string
    {
        return __('sales::invoice.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sales::invoice.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('sales::invoice.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('sales::invoice.customer_currency_info'))
                ->description(__('sales::invoice.customer_currency_info_description'))
                ->schema([
                    TranslatableSelect::make('customer_id')
                        ->relationship('customer', 'name')
                        ->label(__('sales::invoice.customer'))
                        ->searchableFields(['name', 'email', 'contact_person'])
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(2)
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label(__('foundation::partner.name'))
                                ->required()
                                ->maxLength(255),
                            Select::make('type')
                                ->label(__('foundation::partner.type'))
                                ->required()
                                ->options(
                                    collect(\Modules\Foundation\Enums\Partners\PartnerType::cases())
                                        ->mapWithKeys(fn (\Modules\Foundation\Enums\Partners\PartnerType $type) => [$type->value => $type->label()])
                                ),
                            TextInput::make('contact_person')
                                ->label(__('foundation::partner.contact_person'))
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label(__('foundation::partner.email'))
                                ->email()
                                ->maxLength(255),
                            TextInput::make('phone')
                                ->label(__('foundation::partner.phone'))
                                ->maxLength(255),
                            Textarea::make('address')
                                ->label(__('foundation::partner.address'))
                                ->columnSpanFull(),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_partner'))
                        ->createOptionAction(function (Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),
                    TranslatableSelect::forModel('currency_id', Currency::class, 'name')
                        ->label(__('sales::invoice.currency'))
                        ->required()
                        ->live()
                        ->preload()
                        ->searchable()
                        ->default(function (): ?int {
                            $tenant = Filament::getTenant();

                            return $tenant instanceof Company ? $tenant->currency_id : null;
                        })
                        ->afterStateUpdated(function (callable $set, $state) {
                            // Clear any manually set exchange rate when currency changes
                            if ($state) {
                                $set('exchange_rate_at_creation', null);
                            }
                        })
                        ->createOptionForm([
                            TextInput::make('code')
                                ->label(__('foundation::currency.code'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name')
                                ->label(__('foundation::currency.name'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('symbol')
                                ->label(__('foundation::currency.symbol'))
                                ->required()
                                ->maxLength(5),
                            TextInput::make('exchange_rate')
                                ->label(__('foundation::currency.exchange_rate'))
                                ->required()
                                ->numeric()
                                ->default(1),
                            Toggle::make('is_active')
                                ->label(__('foundation::currency.is_active'))
                                ->required()
                                ->default(true),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_currency'))
                        ->createOptionAction(function (Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),

                    TextInput::make('exchange_rate_at_creation')
                        ->label(__('sales::invoice.exchange_rate'))
                        ->numeric()
                        ->step(0.000001)
                        ->minValue(0.000001)
                        ->columnSpan(1)
                        ->visible(function (callable $get) {
                            $currencyId = $get('currency_id');
                            $company = Filament::getTenant();

                            return $currencyId && $company instanceof Company && $currencyId != $company->currency_id;
                        })
                        ->disabled(function (?Invoice $record) {
                            return $record && $record->status !== InvoiceStatus::Draft;
                        })
                        ->helperText(function (callable $get, ?Invoice $record) {
                            // If document is not draft, show locked message
                            if ($record && $record->status !== InvoiceStatus::Draft) {
                                return __('sales::invoice.exchange_rate_locked_helper');
                            }

                            // Show current exchange rate as helper text
                            $currencyId = $get('currency_id');
                            $company = Filament::getTenant();

                            if ($currencyId && $company instanceof Company && $currencyId != $company->currency_id) {
                                $currency = Currency::find($currencyId);
                                if ($currency) {
                                    $latestRate = CurrencyRate::getLatestRate($currency->id, $company->id);
                                    if ($latestRate) {
                                        return __('sales::invoice.exchange_rate_helper_with_current', ['rate' => number_format($latestRate, 6)]);
                                    }
                                }
                            }

                            return __('sales::invoice.exchange_rate_manual_helper');
                        })
                        ->placeholder(function (callable $get) {
                            // Show current rate as placeholder when creating new records
                            $currencyId = $get('currency_id');
                            $company = Filament::getTenant();

                            if ($currencyId && $company instanceof Company && $currencyId != $company->currency_id) {
                                $currency = Currency::find($currencyId);
                                if ($currency) {
                                    $latestRate = CurrencyRate::getLatestRate($currency->id, $company->id);
                                    if ($latestRate) {
                                        return number_format($latestRate, 6);
                                    }
                                }
                            }

                            return null;
                        }),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('sales::invoice.invoice_details'))
                ->description(__('sales::invoice.invoice_details_description'))
                ->schema([
                    TranslatableSelect::forModel('fiscal_position_id', FiscalPosition::class, 'name')
                        ->label(__('sales::invoice.fiscal_position'))
                        ->searchable()
                        ->preload()
                        ->columnSpan(2),
                    DatePicker::make('invoice_date')
                        ->label(__('sales::invoice.invoice_date'))
                        ->default(now())
                        ->required()
                        ->rules([new NotInLockedPeriod]),
                    DatePicker::make('due_date')
                        ->label(__('sales::invoice.due_date'))
                        ->required(),
                    TranslatableSelect::make('payment_term_id')
                        ->relationship('paymentTerm', 'name')
                        ->label(__('sales::invoice.payment_term'))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('sales::invoice.line_items'))
                ->description(__('sales::invoice.line_items_description'))
                ->schema([
                    Repeater::make('invoiceLines')
                        ->label(__('sales::invoice.invoice_lines'))
                        ->table([
                            TableColumn::make(__('sales::invoice.product'))->width('25%'),
                            TableColumn::make(__('sales::invoice.description'))->width('15%'),
                            TableColumn::make(__('sales::invoice.quantity'))->width('10%'),
                            TableColumn::make(__('sales::invoice.unit_price'))->width('15%'),
                            TableColumn::make(__('sales::invoice.tax'))->width('20%'),
                            TableColumn::make(__('sales::invoice.income_account'))->width('15%'),
                        ])
                        ->live()
                        ->reorderable(true)
                        ->deletable(fn (?Invoice $record) => ! $record || $record->status === InvoiceStatus::Draft)
                        ->disabled(fn (?Invoice $record) => $record && $record->status !== InvoiceStatus::Draft)
                        ->minItems(1)
                        ->schema([
                            TranslatableSelect::forModel('product_id', Product::class, 'name')
                                ->label(__('sales::invoice.product'))
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
                                            $set('description', $product->description);
                                            // Convert Money object to string for MoneyInput component
                                            $unitPrice = $product->unit_price;
                                            if ($unitPrice instanceof Money) {
                                                $set('unit_price', $unitPrice->getAmount()->__toString());
                                            } else {
                                                $set('unit_price', $unitPrice);
                                            }
                                            $set('income_account_id', $product->income_account_id);
                                        }
                                    }
                                })
                                ->createOptionForm([
                                    Hidden::make('company_id')
                                        ->default(fn () => Filament::getTenant()?->getKey()),
                                    TextInput::make('name')
                                        ->label(__('product::product.name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('sku')
                                        ->label(__('product::product.sku'))
                                        ->maxLength(255),
                                    Select::make('type')
                                        ->label(__('product::product.type'))
                                        ->required()
                                        ->live()
                                        ->options(
                                            collect(\Modules\Product\Enums\Products\ProductType::cases())
                                                ->mapWithKeys(fn (\Modules\Product\Enums\Products\ProductType $type) => [$type->value => $type->label()])
                                        ),
                                    Textarea::make('description')
                                        ->label(__('product::product.description'))
                                        ->columnSpanFull(),
                                    TranslatableSelect::make('default_inventory_account_id')
                                        ->relationship('inventoryAccount', 'name')
                                        ->label(__('product::product.default_inventory_account'))
                                        ->searchable()
                                        ->preload()
                                        ->searchableFields(['name'])
                                        ->visible(fn ($get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                                        ->required(fn ($get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                                        ->rules(['required_if:type,'.\Modules\Product\Enums\Products\ProductType::Storable->value])
                                        ->createOptionForm([
                                            Hidden::make('company_id')
                                                ->default(fn () => Filament::getTenant()?->getKey()),
                                            TextInput::make('code')
                                                ->label(__('accounting::account.code'))
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('name')
                                                ->label(__('accounting::account.name'))
                                                ->required()
                                                ->maxLength(255),
                                            Select::make('type')
                                                ->label(__('accounting::account.type'))
                                                ->required()
                                                ->options(
                                                    collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())
                                                        ->mapWithKeys(fn (\Modules\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                                )
                                                ->searchable(),
                                            Toggle::make('is_deprecated')
                                                ->label(__('accounting::account.is_deprecated'))
                                                ->default(false),
                                        ])
                                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                                        ->createOptionAction(function (Action $action) {
                                            return $action->modalWidth('lg');
                                        }),
                                    MoneyInput::make('unit_price')
                                        ->label(__('product::product.unit_price'))
                                        ->currencyField('../../currency_id'),
                                    Select::make('income_account_id')
                                        ->relationship('incomeAccount', 'name')
                                        ->label(__('product::product.income_account'))
                                        ->required(),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_product'))
                                ->createOptionAction(function (Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(3),
                            TextInput::make('description')
                                ->label(__('sales::invoice.description'))
                                ->maxLength(255)
                                ->required()
                                ->columnSpan(4),
                            TextInput::make('quantity')
                                ->label(__('sales::invoice.quantity'))
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->columnSpan(2),
                            MoneyInput::make('unit_price')
                                ->label(__('sales::invoice.unit_price'))
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(3),
                            TranslatableSelect::forModel('tax_id', Tax::class, 'name')
                                ->label(__('sales::invoice.tax'))
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
                            TranslatableSelect::forModel('income_account_id', Account::class, 'name')
                                ->label(__('sales::invoice.income_account'))
                                ->searchable()
                                ->preload()
                                ->modifyQueryUsing(fn ($query) => $query->where('type', 'income'))
                                ->required()
                                ->columnSpan(3),
                        ])
                        ->columns(18),
                ])->columnSpanFull(),

            Section::make(__('sales::invoice.company_currency_totals'))
                ->schema([
                    TextInput::make('exchange_rate_at_creation')
                        ->label(__('sales::invoice.exchange_rate_at_creation'))
                        ->numeric()
                        ->disabled()
                        ->visible(fn (?Invoice $record) => $record && $record->exchange_rate_at_creation),

                    MoneyInput::make('total_amount_company_currency')
                        ->label(__('sales::invoice.total_amount_company_currency'))
                        ->currencyField('../../company.currency_id')
                        ->disabled()
                        ->visible(fn (?Invoice $record) => $record && $record->total_amount_company_currency),

                    MoneyInput::make('total_tax_company_currency')
                        ->label(__('sales::invoice.total_tax_company_currency'))
                        ->currencyField('../../company.currency_id')
                        ->disabled()
                        ->visible(fn (?Invoice $record) => $record && $record->total_tax_company_currency),
                ])
                ->columns(3)
                ->visible(fn (?Invoice $record) => $record && ($record->exchange_rate_at_creation || $record->total_amount_company_currency)),
        ]);
    }

    /**
     * @return Builder<Invoice>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company.currency', 'customer', 'currency', 'journalEntry', 'fiscalPosition']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Most important: Reference number (always visible)
                TextColumn::make('reference')
                    ->label(__('sales::invoice.reference'))
                    ->searchable(['invoice_number'])
                    ->getStateUsing(function (Invoice $record): string {
                        if ($record->invoice_number) {
                            return $record->invoice_number;
                        }

                        return 'DRAFT-'.str_pad((string) $record->id, 5, '0', STR_PAD_LEFT);
                    })
                    ->badge()
                    ->color(fn (Invoice $record): string => $record->invoice_number ? 'success' : 'warning')
                    ->icon(fn (Invoice $record): string => $record->invoice_number ? 'heroicon-m-check-circle' : 'heroicon-m-pencil-square')
                    ->sortable(),

                // Customer (critical for identification)
                TextColumn::make('customer.name')
                    ->label(__('sales::invoice.customer'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Status (critical for workflow)
                TextColumn::make('status')
                    ->label(__('sales::invoice.status'))
                    ->badge()
                    ->colors([
                        'success' => InvoiceStatus::Posted,
                        'danger' => InvoiceStatus::Cancelled,
                        'warning' => InvoiceStatus::Draft,
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => InvoiceStatus::Posted,
                        'heroicon-m-x-circle' => InvoiceStatus::Cancelled,
                        'heroicon-m-pencil-square' => InvoiceStatus::Draft,
                    ])
                    ->searchable()
                    ->sortable(),

                // Invoice Date (important for chronological sorting)
                TextColumn::make('invoice_date')
                    ->label(__('sales::invoice.date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                // Due Date (critical for cash flow management)
                TextColumn::make('due_date')
                    ->label(__('sales::invoice.due_date'))
                    ->date()
                    ->sortable(),

                // Payment Terms
                TextColumn::make('paymentTerm.name')
                    ->label(__('sales::invoice.payment_term'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // Payment State (critical for collections)
                TextColumn::make('paymentState')
                    ->label(__('sales::invoice.payment_state'))
                    ->formatStateUsing(fn (\Modules\Foundation\Enums\Shared\PaymentState $state): string => $state->label())
                    ->badge()
                    ->color(fn (\Modules\Foundation\Enums\Shared\PaymentState $state): string => $state->color()),
                // Total Amount (critical financial information)
                MoneyColumn::make('total_amount')
                    ->label(__('sales::invoice.total_amount'))
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                // Currency (important for multi-currency)
                TextColumn::make('currency.code')
                    ->label(__('sales::invoice.currency'))
                    ->badge()
                    ->toggleable(),

                // Company (for multi-company setups)
                TextColumn::make('company.name')
                    ->label(__('sales::invoice.company'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('exchange_rate_at_creation')
                    ->label(__('sales::invoice.exchange_rate'))
                    ->numeric(decimalPlaces: 6)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->exchange_rate_at_creation),

                MoneyColumn::make('total_amount_company_currency')
                    ->label(__('sales::invoice.total_amount_company_currency'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->total_amount_company_currency),
                // Posted Date (important for audit trail)
                TextColumn::make('posted_at')
                    ->label(__('sales::invoice.posted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                // Additional columns (hidden by default for cleaner view)
                TextColumn::make('created_at')
                    ->label(__('sales::invoice.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('sales::invoice.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('viewPdf')
                        ->label(__('View PDF'))
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn (Invoice $record) => route('invoices.pdf', $record))
                        ->openUrlInNewTab(),

                    Action::make('downloadPdf')
                        ->label(__('Download PDF'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn (Invoice $record) => route('invoices.pdf.download', $record)),
                ])
                    ->label(__('PDF'))
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->button(),
                Action::make('confirm')
                    ->label(__('sales::invoice.confirm'))
                    ->action(function (Invoice $record) {
                        $invoiceService = app(InvoiceService::class);
                        try {
                            $user = Auth::user();
                            if (! $user) {
                                throw new Exception('User must be authenticated to confirm invoice');
                            }
                            $invoiceService->confirm($record, $user);
                            Notification::make()
                                ->title(__('sales::invoice.invoice_confirmed_successfully'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('sales::invoice.error_confirming_invoice'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Draft),
                Action::make('register_payment')
                    ->label(__('sales::invoice.register_payment'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading(__('sales::invoice.register_payment'))
                    ->modalDescription(__('sales::invoice.payments_relation_manager.payment_details'))
                    ->schema([
                        Select::make('journal_id')
                            ->label(__('payment::payment.form.journal_id'))
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
                            ->label(__('payment::payment.form.payment_date'))
                            ->default(now())
                            ->required(),
                        MoneyInput::make('amount')
                            ->label(__('payment::payment.form.amount'))
                            ->currencyField('currency_id')
                            ->default(fn (Invoice $record) => $record->getRemainingAmount())
                            ->required(),
                        TextInput::make('reference')
                            ->label(__('payment::payment.form.reference'))
                            ->placeholder(__('Optional reference')),
                        Hidden::make('currency_id')
                            ->default(fn (Invoice $record) => $record->currency_id),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        try {
                            $currency = $record->currency;

                            // Create payment document link DTO
                            $documentLink = new CreatePaymentDocumentLinkDTO(
                                document_type: 'invoice',
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
                                payment_type: PaymentType::Inbound,
                                payment_method: PaymentMethod::BankTransfer,
                                paid_to_from_partner_id: $record->customer_id,
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
                                ->title(__('payment::payment.action.confirm.notification.success'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('payment::payment.action.confirm.notification.error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(
                        fn (Invoice $record) => $record->status === InvoiceStatus::Posted &&
                        ! $record->getRemainingAmount()->isZero()
                    ),
                // Action::make('resetToDraft')
                //     ->label(__('invoice.reset_to_draft'))
                //     ->action(function (Invoice $record, array $data) {
                //         $invoiceService = app(InvoiceService::class);
                //         try {
                //             $invoiceService->resetToDraft($record, Auth::user(), $data['reason']);
                //             Notification::make()
                //                 ->title(__('invoice.invoice_reset_to_draft_successfully'))
                //                 ->success()
                //                 ->send();
                //         } catch (\Exception $e) {
                //             Notification::make()
                //                 ->title(__('invoice.error_resetting_invoice_to_draft'))
                //                 ->body($e->getMessage())
                //                 ->danger()
                //                 ->send();
                //         }
                //     })
                //     ->form([
                //         Forms\Components\Textarea::make('reason')->label(__('invoice.reason'))->required(),
                //     ])
                //     ->requiresConfirmation()
                //     ->visible(fn(Invoice $record) => $record->status === InvoiceStatus::Posted),
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
            InvoiceLinesRelationManager::class,
            PaymentsRelationManager::class,
            AdjustmentDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
