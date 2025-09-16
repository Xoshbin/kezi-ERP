<?php

namespace App\Filament\Clusters\Accounting\Resources\Invoices;

use App\Actions\Payments\CreatePaymentAction;
use App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Enums\Partners\PartnerType;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentType;
use App\Enums\Sales\InvoiceStatus;
use App\Enums\Shared\PaymentState;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Clusters\Accounting\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Clusters\Accounting\Resources\Invoices\RelationManagers\AdjustmentDocumentsRelationManager;
use App\Filament\Clusters\Accounting\Resources\Invoices\RelationManagers\InvoiceLinesRelationManager;
use App\Filament\Clusters\Accounting\Resources\Invoices\RelationManagers\PaymentsRelationManager;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\FiscalPosition;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Product;
use App\Models\Tax;
use App\Rules\NotInLockedPeriod;
use App\Services\InvoiceService;
use App\Services\PaymentService;
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
        return __('invoice.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('invoice.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('invoice.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('invoice.customer_currency_info'))
                ->description(__('invoice.customer_currency_info_description'))
                ->schema([
                    TranslatableSelect::make('customer_id')
                        ->relationship('customer', 'name')
                        ->label(__('invoice.customer'))
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
                        ->label(__('invoice.currency'))
                        ->required()
                        ->live()
                        ->preload()
                        ->searchable()
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
                        ->label(__('invoice.current_exchange_rate'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(function (callable $get) {
                            $currencyId = $get('currency_id');
                            $company = Filament::getTenant();

                            return $currencyId && $company instanceof Company && $currencyId != $company->currency_id;
                        })
                        ->helperText(__('invoice.exchange_rate_helper')),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('invoice.invoice_details'))
                ->description(__('invoice.invoice_details_description'))
                ->schema([
                    TranslatableSelect::forModel('fiscal_position_id', FiscalPosition::class, 'name')
                        ->label(__('invoice.fiscal_position'))
                        ->searchable()
                        ->preload()
                        ->columnSpan(2),
                    DatePicker::make('invoice_date')
                        ->label(__('invoice.invoice_date'))
                        ->default(now())
                        ->required()
                        ->rules([new NotInLockedPeriod]),
                    DatePicker::make('due_date')
                        ->label(__('invoice.due_date'))
                        ->required(),
                    TranslatableSelect::make('payment_term_id')
                        ->relationship('paymentTerm', 'name')
                        ->label(__('invoice.payment_term'))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('invoice.line_items'))
                ->description(__('invoice.line_items_description'))
                ->schema([
                    Repeater::make('invoiceLines')
                        ->label(__('invoice.invoice_lines'))
                        ->table([
                            TableColumn::make(__('invoice.product'))->width('25%'),
                            TableColumn::make(__('invoice.description'))->width('15%'),
                            TableColumn::make(__('invoice.quantity'))->width('10%'),
                            TableColumn::make(__('invoice.unit_price'))->width('15%'),
                            TableColumn::make(__('invoice.tax'))->width('20%'),
                            TableColumn::make(__('invoice.income_account'))->width('15%'),
                        ])
                        ->live()
                        ->reorderable(true)
                        ->deletable(fn (?Invoice $record) => ! $record || $record->status === InvoiceStatus::Draft)
                        ->disabled(fn (?Invoice $record) => $record && $record->status !== InvoiceStatus::Draft)
                        ->minItems(1)
                        ->schema([
                            TranslatableSelect::forModel('product_id', Product::class, 'name')
                                ->label(__('invoice.product'))
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
                                            $set('unit_price', $product->unit_price);
                                            $set('income_account_id', $product->income_account_id);
                                        }
                                    }
                                })
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label(__('product.name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('sku')
                                        ->label(__('product.sku'))
                                        ->maxLength(255),
                                    Textarea::make('description')
                                        ->label(__('product.description'))
                                        ->columnSpanFull(),
                                    MoneyInput::make('unit_price')
                                        ->label(__('product.unit_price'))
                                        ->currencyField('../../currency_id'),
                                    Select::make('income_account_id')
                                        ->relationship('incomeAccount', 'name')
                                        ->label(__('product.income_account'))
                                        ->required(),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_product'))
                                ->createOptionAction(function (Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(3),
                            TextInput::make('description')
                                ->label(__('invoice.description'))
                                ->maxLength(255)
                                ->required()
                                ->columnSpan(4),
                            TextInput::make('quantity')
                                ->label(__('invoice.quantity'))
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->columnSpan(2),
                            MoneyInput::make('unit_price')
                                ->label(__('invoice.unit_price'))
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(3),
                            TranslatableSelect::forModel('tax_id', Tax::class, 'name')
                                ->label(__('invoice.tax'))
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
                                        ->numeric()
                                        ->suffix('%'),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_tax'))
                                ->createOptionAction(function (Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(3),
                            TranslatableSelect::forModel('income_account_id', Account::class, 'name')
                                ->label(__('invoice.income_account'))
                                ->searchable()
                                ->preload()
                                ->modifyQueryUsing(fn ($query) => $query->where('type', 'income'))
                                ->required()
                                ->columnSpan(3),
                        ])
                        ->columns(18),
                ])->columnSpanFull(),

            Section::make(__('invoice.company_currency_totals'))
                ->schema([
                    TextInput::make('exchange_rate_at_creation')
                        ->label(__('invoice.exchange_rate_at_creation'))
                        ->numeric()
                        ->disabled()
                        ->visible(fn (?Invoice $record) => $record && $record->exchange_rate_at_creation),

                    MoneyInput::make('total_amount_company_currency')
                        ->label(__('invoice.total_amount_company_currency'))
                        ->currencyField('../../company.currency_id')
                        ->disabled()
                        ->visible(fn (?Invoice $record) => $record && $record->total_amount_company_currency),

                    MoneyInput::make('total_tax_company_currency')
                        ->label(__('invoice.total_tax_company_currency'))
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
                    ->label(__('invoice.reference'))
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
                    ->label(__('invoice.customer'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Status (critical for workflow)
                TextColumn::make('status')
                    ->label(__('invoice.status'))
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
                    ->label(__('invoice.date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                // Due Date (critical for cash flow management)
                TextColumn::make('due_date')
                    ->label(__('invoice.due_date'))
                    ->date()
                    ->sortable(),

                // Payment Terms
                TextColumn::make('paymentTerm.name')
                    ->label(__('invoice.payment_term'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // Payment State (critical for collections)
                TextColumn::make('paymentState')
                    ->label(__('invoice.payment_state'))
                    ->formatStateUsing(fn (PaymentState $state): string => $state->label())
                    ->badge()
                    ->color(fn (PaymentState $state): string => $state->color()),
                // Total Amount (critical financial information)
                MoneyColumn::make('total_amount')
                    ->label(__('invoice.total'))
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                // Currency (important for multi-currency)
                TextColumn::make('currency.code')
                    ->label(__('invoice.currency'))
                    ->badge()
                    ->toggleable(),

                // Company (for multi-company setups)
                TextColumn::make('company.name')
                    ->label(__('invoice.company'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('exchange_rate_at_creation')
                    ->label(__('invoice.exchange_rate'))
                    ->numeric(decimalPlaces: 6)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->exchange_rate_at_creation),

                MoneyColumn::make('total_amount_company_currency')
                    ->label(__('invoice.total_amount_company_currency'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->total_amount_company_currency),
                // Posted Date (important for audit trail)
                TextColumn::make('posted_at')
                    ->label(__('invoice.posted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                // Additional columns (hidden by default for cleaner view)
                TextColumn::make('created_at')
                    ->label(__('invoice.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('invoice.updated_at'))
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
                    ->label(__('invoice.confirm'))
                    ->action(function (Invoice $record) {
                        $invoiceService = app(InvoiceService::class);
                        try {
                            $user = Auth::user();
                            if (! $user) {
                                throw new Exception('User must be authenticated to confirm invoice');
                            }
                            $invoiceService->confirm($record, $user);
                            Notification::make()
                                ->title(__('invoice.invoice_confirmed_successfully'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('invoice.error_confirming_invoice'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Draft),
                Action::make('register_payment')
                    ->label(__('Register Payment'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading(__('Register Payment'))
                    ->modalDescription(__('Register a payment for this invoice'))
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
                            ->default(fn (Invoice $record) => $record->getRemainingAmount())
                            ->required(),
                        TextInput::make('reference')
                            ->label(__('payment.form.reference'))
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
