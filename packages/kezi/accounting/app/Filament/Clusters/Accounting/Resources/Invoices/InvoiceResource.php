<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices;

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
use Kezi\Accounting\Enums\Accounting\TaxType;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\CreateInvoice;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ListInvoices;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\RelationManagers\AdjustmentDocumentsRelationManager;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\RelationManagers\InvoiceLinesRelationManager;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\RelationManagers\PaymentsRelationManager;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\FiscalPosition;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\Tax;
use Kezi\Accounting\Rules\NotInLockedPeriod;
use Kezi\Foundation\Enums\Incoterm;
use Kezi\Foundation\Filament\Forms\Components\ExchangeRateInput;
use Kezi\Foundation\Filament\Forms\Components\MoneyInput;
use Kezi\Foundation\Filament\Helpers\DocumentAttachmentsHelper;
use Kezi\Foundation\Filament\Helpers\DocumentTotalsHelper;
use Kezi\Foundation\Filament\Tables\Columns\MoneyColumn;
use Kezi\Foundation\Models\Currency;
use Kezi\Payment\Actions\Payments\CreatePaymentAction;
use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Kezi\Payment\Enums\Payments\PaymentMethod;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Payment\Services\PaymentService;
use Kezi\Product\Models\Product;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Services\InvoiceService;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.transactions');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::invoice.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::invoice.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::invoice.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('accounting::invoice.customer_currency_info'))
                ->description(__('accounting::invoice.customer_currency_info_description'))
                ->schema([
                    TranslatableSelect::make('customer_id')
                        ->relationship('customer', 'name')
                        ->label(__('accounting::invoice.customer'))
                        ->searchableFields(['name', 'email', 'contact_person'])
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(2)
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label(__('accounting::partner.name'))
                                ->required()
                                ->maxLength(255),
                            Select::make('type')
                                ->label(__('accounting::partner.type'))
                                ->required()
                                ->options(
                                    collect(\Kezi\Foundation\Enums\Partners\PartnerType::cases())
                                        ->mapWithKeys(fn (\Kezi\Foundation\Enums\Partners\PartnerType $type) => [$type->value => $type->label()])
                                ),
                            TextInput::make('contact_person')
                                ->label(__('accounting::partner.contact_person'))
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label(__('accounting::partner.email'))
                                ->email()
                                ->maxLength(255),
                            TextInput::make('phone')
                                ->label(__('accounting::partner.phone'))
                                ->maxLength(255),
                            Textarea::make('address')
                                ->label(__('accounting::partner.address'))
                                ->columnSpanFull(),
                        ])
                        ->createOptionModalHeading(__('accounting::invoice.modal_title_create_partner'))
                        ->createOptionAction(function (Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),
                    TranslatableSelect::forModel('currency_id', Currency::class, 'name')
                        ->label(__('accounting::invoice.currency'))
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
                                ->label(__('accounting::invoice.currency_code'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name')
                                ->label(__('accounting::invoice.currency_label'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('symbol')
                                ->label(__('accounting::invoice.currency_symbol'))
                                ->required()
                                ->maxLength(5),
                            TextInput::make('exchange_rate')
                                ->label(__('accounting::invoice.currency_exchange_rate'))
                                ->required()
                                ->numeric()
                                ->default(1),
                            Toggle::make('is_active')
                                ->label(__('accounting::invoice.currency_is_active'))
                                ->required()
                                ->default(true),
                        ])
                        ->createOptionModalHeading(__('accounting::invoice.modal_title_create_currency'))
                        ->createOptionAction(function (Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),

                    ExchangeRateInput::make('exchange_rate_at_creation')
                        ->columnSpan(1)
                        ->disabled(function (?Invoice $record) {
                            return $record && $record->status !== InvoiceStatus::Draft;
                        })
                        ->helperText(function (callable $get, ?Invoice $record) {
                            if ($record && $record->status !== InvoiceStatus::Draft) {
                                return __('accounting::invoice.exchange_rate_locked_helper');
                            }

                            return null; // Fallback to component default
                        }),
                    Select::make('incoterm')
                        ->label(__('accounting::invoice.incoterm'))
                        ->options(Incoterm::class)
                        ->searchable()
                        ->preload(),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('accounting::invoice.invoice_details'))
                ->description(__('accounting::invoice.invoice_details_description'))
                ->schema([
                    TranslatableSelect::forModel('fiscal_position_id', FiscalPosition::class, 'name')
                        ->label(__('accounting::invoice.fiscal_position'))
                        ->searchable()
                        ->preload()
                        ->columnSpan(2),
                    DatePicker::make('invoice_date')
                        ->label(__('accounting::invoice.invoice_date'))
                        ->default(now())
                        ->required()
                        ->rules([new NotInLockedPeriod]),
                    DatePicker::make('due_date')
                        ->label(__('accounting::invoice.due_date'))
                        ->required(),
                    TranslatableSelect::make('payment_term_id')
                        ->relationship('paymentTerm', 'name')
                        ->label(__('accounting::invoice.payment_term'))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('accounting::invoice.line_items'))
                ->description(__('accounting::invoice.line_items_description'))
                ->schema([
                    Repeater::make('invoiceLines')
                        ->label(__('accounting::invoice.invoice_lines'))
                        ->addActionLabel(__('accounting::invoice.add_invoice_line'))
                        ->table([
                            TableColumn::make(__('accounting::invoice.product'))->width('25%'),
                            TableColumn::make(__('accounting::invoice.description'))->width('15%'),
                            TableColumn::make(__('accounting::invoice.quantity'))->width('10%'),
                            TableColumn::make(__('accounting::invoice.unit_price'))->width('15%'),
                            TableColumn::make(__('accounting::invoice.tax'))->width('20%'),
                            TableColumn::make(__('accounting::invoice.income_account'))->width('15%'),
                        ])
                        ->live()
                        ->reorderable(true)
                        ->deletable(fn (?Invoice $record) => ! $record || $record->status === InvoiceStatus::Draft)
                        ->disabled(fn (?Invoice $record) => $record && $record->status !== InvoiceStatus::Draft)
                        ->minItems(1)
                        ->schema([
                            TranslatableSelect::forModel('product_id', Product::class, 'name')
                                ->label(__('accounting::invoice.product'))
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
                                        ->label(__('accounting::invoice.product_name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('sku')
                                        ->label(__('accounting::invoice.product_sku'))
                                        ->maxLength(255),
                                    Select::make('type')
                                        ->label(__('accounting::invoice.product_type'))
                                        ->required()
                                        ->live()
                                        ->options(
                                            collect(\Kezi\Product\Enums\Products\ProductType::cases())
                                                ->mapWithKeys(fn (\Kezi\Product\Enums\Products\ProductType $type) => [$type->value => $type->label()])
                                        ),
                                    Textarea::make('description')
                                        ->label(__('accounting::invoice.product_description'))
                                        ->columnSpanFull(),
                                    TranslatableSelect::forModel('default_inventory_account_id', Account::class, 'name')
                                        ->label(__('accounting::invoice.product_default_inventory_account'))
                                        ->searchable()
                                        ->preload()
                                        ->searchableFields(['name', 'code'])
                                        ->visible(fn ($get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value)
                                        ->required(fn ($get) => $get('type') === \Kezi\Product\Enums\Products\ProductType::Storable->value)
                                        ->rules(['required_if:type,'.\Kezi\Product\Enums\Products\ProductType::Storable->value])
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
                                                    collect(\Kezi\Accounting\Enums\Accounting\AccountType::cases())
                                                        ->mapWithKeys(fn (\Kezi\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                                )
                                                ->searchable(),
                                            Toggle::make('is_deprecated')
                                                ->label(__('accounting::account.is_deprecated'))
                                                ->default(false),
                                        ])
                                        ->createOptionModalHeading(__('accounting::invoice.modal_title_create_account'))
                                        ->createOptionAction(function (Action $action) {
                                            return $action->modalWidth('lg');
                                        }),
                                    MoneyInput::make('unit_price')
                                        ->label(__('accounting::invoice.product_unit_price'))
                                        ->currencyField('../../currency_id'),
                                    TranslatableSelect::forModel('income_account_id', Account::class, 'name')
                                        ->label(__('accounting::invoice.product_income_account'))
                                        ->searchable()
                                        ->preload()
                                        ->searchableFields(['name', 'code'])
                                        ->required(),
                                ])
                                ->createOptionModalHeading(__('accounting::invoice.modal_title_create_product'))
                                ->createOptionAction(function (Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(3),
                            TextInput::make('description')
                                ->label(__('accounting::invoice.description'))
                                ->maxLength(255)
                                ->required()
                                ->columnSpan(4),
                            TextInput::make('quantity')
                                ->label(__('accounting::invoice.quantity'))
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->columnSpan(2),
                            MoneyInput::make('unit_price')
                                ->label(__('accounting::invoice.unit_price'))
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(3),
                            TranslatableSelect::forModel('tax_id', Tax::class, 'name')
                                ->label(__('accounting::invoice.tax'))
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
                                ->createOptionModalHeading(__('accounting::invoice.modal_title_create_tax'))
                                ->createOptionAction(function (Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(3),
                            TranslatableSelect::forModel('income_account_id', Account::class, 'name')
                                ->label(__('accounting::invoice.income_account'))
                                ->searchable()
                                ->preload()
                                ->modifyQueryUsing(fn ($query) => $query->where('type', 'income'))
                                ->required()
                                ->columnSpan(3),
                            DatePicker::make('deferred_start_date')
                                ->label(__('accounting::invoice.deferred_start_date'))
                                ->columnSpan(3),
                            DatePicker::make('deferred_end_date')
                                ->label(__('accounting::invoice.deferred_end_date'))
                                ->columnSpan(3),
                        ])
                        ->columns(18),
                ])->columnSpanFull(),

            DocumentTotalsHelper::make(
                linesKey: 'invoiceLines',
                translationPrefix: 'accounting::invoice'
            ),

            DocumentAttachmentsHelper::makeSection(
                directory: 'invoices',
                disabledCallback: fn (?Invoice $record) => $record && $record->status !== InvoiceStatus::Draft,
                deletableCallback: fn (?Invoice $record) => $record === null || $record->status === InvoiceStatus::Draft
            ),
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
                    ->label(__('accounting::invoice.reference'))
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
                    ->label(__('accounting::invoice.customer'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Status (critical for workflow)
                TextColumn::make('status')
                    ->label(__('accounting::invoice.status'))
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
                    ->label(__('accounting::invoice.date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                // Due Date (critical for cash flow management)
                TextColumn::make('due_date')
                    ->label(__('accounting::invoice.due_date'))
                    ->date()
                    ->sortable(),

                // Payment Terms
                TextColumn::make('paymentTerm.name')
                    ->label(__('accounting::invoice.payment_term'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // Payment State (critical for collections)
                TextColumn::make('paymentState')
                    ->label(__('accounting::invoice.payment_state'))
                    ->formatStateUsing(fn (\Kezi\Foundation\Enums\Shared\PaymentState $state): string => $state->label())
                    ->badge()
                    ->color(fn (\Kezi\Foundation\Enums\Shared\PaymentState $state): string => $state->color()),
                // Total Amount (critical financial information)
                MoneyColumn::make('total_amount')
                    ->label(__('accounting::invoice.total_amount'))
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                // Currency (important for multi-currency)
                TextColumn::make('currency.code')
                    ->label(__('accounting::invoice.currency'))
                    ->badge()
                    ->toggleable(),

                // Company (for multi-company setups)
                TextColumn::make('company.name')
                    ->label(__('accounting::invoice.company'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('exchange_rate_at_creation')
                    ->label(__('accounting::invoice.exchange_rate'))
                    ->numeric(decimalPlaces: 6)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->exchange_rate_at_creation),

                MoneyColumn::make('total_amount_company_currency')
                    ->label(__('accounting::invoice.total_amount_company_currency'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->total_amount_company_currency),
                // Posted Date (important for audit trail)
                TextColumn::make('posted_at')
                    ->label(__('accounting::invoice.posted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                // Additional columns (hidden by default for cleaner view)
                TextColumn::make('created_at')
                    ->label(__('accounting::invoice.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('accounting::invoice.updated_at'))
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
                ]),
                ActionGroup::make([
                    Action::make('viewPdf')
                        ->label(__('accounting::invoice.view_pdf'))
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn (Invoice $record) => route('invoices.pdf', $record))
                        ->openUrlInNewTab(),

                    Action::make('downloadPdf')
                        ->label(__('accounting::invoice.download_pdf'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn (Invoice $record) => route('invoices.pdf.download', $record)),
                ])
                    ->label(__('PDF'))
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->button(),
                Action::make('confirm')
                    ->label(__('accounting::invoice.confirm'))
                    ->action(function (Invoice $record) {
                        $invoiceService = app(InvoiceService::class);
                        try {
                            $user = Auth::user();
                            if (! $user) {
                                throw new Exception('User must be authenticated to confirm invoice');
                            }
                            $invoiceService->confirm($record, $user);
                            Notification::make()
                                ->title(__('accounting::invoice.invoice_confirmed_successfully'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('accounting::invoice.error_confirming_invoice'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Draft),
                Action::make('register_payment')
                    ->label(__('accounting::invoice.register_payment'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading(__('accounting::invoice.register_payment'))
                    ->modalDescription(__('accounting::invoice.payments_relation_manager.payment_details'))
                    ->schema([
                        Select::make('journal_id')
                            ->label(__('accounting::payment.form.journal_id'))
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
                            ->label(__('accounting::payment.form.payment_date'))
                            ->default(now())
                            ->required(),
                        MoneyInput::make('amount')
                            ->label(__('accounting::payment.form.amount'))
                            ->currencyField('currency_id')
                            ->default(fn (Invoice $record) => $record->getRemainingAmount())
                            ->required(),
                        TextInput::make('reference')
                            ->label(__('accounting::payment.form.reference'))
                            ->placeholder(__('accounting::invoice.optional_reference')),
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
                                ->title(__('accounting::payment.action.confirm.notification.success'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('accounting::payment.action.confirm.notification.error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(
                        fn (Invoice $record) => $record->status === InvoiceStatus::Posted &&
                        ! $record->getRemainingAmount()->isZero()
                    ),
                Action::make('resetToDraft')
                    ->label(__('accounting::invoice.reset_to_draft'))
                    ->color('warning')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Posted && $record->isNotPaid())
                    ->form([
                        Textarea::make('reason')
                            ->label(__('accounting::invoice.reason'))
                            ->required(),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        $invoiceService = app(InvoiceService::class);
                        try {
                            $user = Auth::user();

                            if (! $user) {
                                throw new \Exception('User must be authenticated');
                            }

                            $invoiceService->resetToDraft($record, $user, $data['reason']);
                            Notification::make()
                                ->title(__('accounting::invoice.notification.reset_success'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('accounting::invoice.notification.reset_error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}
