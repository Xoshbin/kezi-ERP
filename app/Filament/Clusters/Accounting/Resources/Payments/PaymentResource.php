<?php

namespace App\Filament\Clusters\Accounting\Resources\Payments;

use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Enums\Purchases\VendorBillStatus;
use App\Enums\Sales\InvoiceStatus;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\CreatePayment;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\ListPayments;
use App\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\BankStatementLinesRelationManager;
use App\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\InvoicesRelationManager;
use App\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\JournalEntriesRelationManager;
use App\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\VendorBillsRelationManager;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Support\TranslatableSelect;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\VendorBill;
use App\Services\PaymentService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = AccountingCluster::class;
    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.banking_cash');
    }

    public static function getModelLabel(): string
    {
        return __('payment.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payment.model_plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('payment.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payment.form.payment_information'))
                ->description(__('payment.form.payment_information_description'))
                ->schema([
                    TranslatableSelect::make('journal_id', \App\Models\Journal::class, __('payment.form.journal_id'))
                        ->required()
                        ->columnSpan(2),
                    TranslatableSelect::make('currency_id', \App\Models\Currency::class, __('payment.form.currency_id'))
                        ->required()
                        ->live()
                        ->columnSpan(2)
                        ->default(fn() => \Filament\Facades\Filament::getTenant()?->currency_id),
                    DatePicker::make('payment_date')
                        ->default(now())
                        ->label(__('payment.form.payment_date'))
                        ->required()
                        ->columnSpan(2),
                    TextInput::make('reference')
                        ->label(__('payment.form.reference'))
                        ->maxLength(255)
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('payment.form.payment_type_and_purpose'))
                ->description(__('payment.form.payment_type_and_purpose_description'))
                ->schema([
                    Select::make('payment_type')
                        ->label(__('payment.form.payment_type'))
                        ->options(collect(PaymentType::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                        ->required()
                        ->live()
                        ->columnSpan(1),
                    Select::make('payment_purpose')
                        ->label(__('payment.form.payment_purpose'))
                        ->options(function (Get $get) {
                            $paymentType = $get('payment_type');
                            if (!$paymentType) {
                                return [];
                            }

                            $purposes = $paymentType === PaymentType::Inbound->value
                                ? PaymentPurpose::inboundPurposes()
                                : PaymentPurpose::outboundPurposes();

                            return collect($purposes)->mapWithKeys(fn($purpose) => [$purpose->value => $purpose->label()]);
                        })
                        ->required()
                        ->live()
                        ->columnSpan(1),
                    Select::make('status')
                        ->label(__('payment.form.status'))
                        ->options(collect(PaymentStatus::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->columnSpanFull(),

            // Settlement Payment Section
            Section::make(__('payment.form.document_links'))
                ->description(__('payment.form.document_links_description'))
                ->schema([
                    Repeater::make('document_links')
                        ->label(__('payment.form.document_links'))
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->disabled(fn (?Payment $record) => $record && $record->status !== PaymentStatus::Draft)
                        ->schema([
                            Select::make('document_type')
                                ->label(__('payment.form.document_type'))
                                ->options(function (Get $get) {
                                    $paymentType = $get('../../payment_type');
                                    if ($paymentType === PaymentType::Inbound->value) {
                                        return ['invoice' => __('payment.form.document_type.invoice')];
                                    } elseif ($paymentType === PaymentType::Outbound->value) {
                                        return ['vendor_bill' => __('payment.form.document_type.vendor_bill')];
                                    }
                                    return [
                                        'invoice' => __('payment.form.document_type.invoice'),
                                        'vendor_bill' => __('payment.form.document_type.vendor_bill'),
                                    ];
                                })
                                ->required()
                                ->live()
                                ->columnSpan(1),
                            Select::make('document_id')
                                ->label(__('payment.form.document_id'))
                                ->options(function (Get $get) {
                                    $type = $get('document_type');
                                    if ($type === 'invoice') {
                                        return Invoice::where('status', InvoiceStatus::Posted)->pluck('invoice_number', 'id');
                                    }
                                    if ($type === 'vendor_bill') {
                                        return VendorBill::where('status', VendorBillStatus::Posted)->pluck('bill_reference', 'id');
                                    }
                                    return [];
                                })
                                ->searchable()
                                ->required()
                                ->columnSpan(1),
                            MoneyInput::make('amount_applied')
                                ->label(__('payment.form.amount_applied'))
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(1),
                        ])
                        ->columns(3)
                        ->afterStateUpdated(function (Get $get, callable $set) {
                            $links = $get('document_links') ?? [];
                            $total = 0;
                            foreach ($links as $link) {
                                $total += (float)($link['amount_applied'] ?? 0);
                            }
                            $set('amount', $total);
                        }),
                    MoneyInput::make('amount')
                        ->label(__('payment.form.amount'))
                        ->currencyField('currency_id')
                        ->readOnly()
                        ->columnSpan(2),
                ])
                ->visible(fn (Get $get) => $get('payment_purpose') === PaymentPurpose::Settlement->value)
                ->columnSpanFull(),

            // Direct Payment Section
            Section::make(__('payment.form.direct_payment'))
                ->description(__('payment.form.direct_payment_description'))
                ->schema([
                    TranslatableSelect::make('partner_id', \App\Models\Partner::class, __('payment.form.partner'))
                        ->required()
                        ->columnSpan(2),
                    MoneyInput::make('amount')
                        ->label(__('payment.form.amount'))
                        ->currencyField('currency_id')
                        ->required()
                        ->columnSpan(2),
                    TranslatableSelect::make('counterpart_account_id', \App\Models\Account::class, __('payment.form.counterpart_account'))
                        ->required()
                        ->columnSpan(4),
                ])
                ->visible(fn (Get $get) => $get('payment_purpose') !== PaymentPurpose::Settlement->value)
                ->columns(4)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Reference (most important for identification)
                TextColumn::make('reference')
                    ->label(__('payment.reference'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->getStateUsing(function (Payment $record): string {
                        if ($record->reference) {
                            return $record->reference;
                        }
                        return 'DRAFT-' . str_pad($record->id, 5, '0', STR_PAD_LEFT);
                    })
                    ->badge()
                    ->color(fn (Payment $record): string => $record->reference ? 'success' : 'warning')
                    ->icon(fn (Payment $record): string => $record->reference ? 'heroicon-m-check-circle' : 'heroicon-m-pencil-square'),

                // Partner (critical for identification)
                TextColumn::make('partner.name')
                    ->label(__('payment.partner'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Payment Type (critical for understanding direction)
                TextColumn::make('payment_type')
                    ->label(__('payment.type'))
                    ->formatStateUsing(fn(PaymentType $state): string => $state->label())
                    ->badge()
                    ->color(fn(PaymentType $state): string => match($state) {
                        PaymentType::Inbound => 'success',
                        PaymentType::Outbound => 'danger',
                    })
                    ->icons([
                        'heroicon-m-arrow-down-circle' => PaymentType::Inbound,
                        'heroicon-m-arrow-up-circle' => PaymentType::Outbound,
                    ])
                    ->searchable(),

                // Status (critical for workflow)
                TextColumn::make('status')
                    ->label(__('payment.status'))
                    ->formatStateUsing(fn(PaymentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn(PaymentStatus $state): string => match($state) {
                        PaymentStatus::Draft => 'gray',
                        PaymentStatus::Confirmed => 'warning',
                        PaymentStatus::Reconciled => 'success',
                        PaymentStatus::Canceled => 'danger',
                    })
                    ->icons([
                        'heroicon-m-pencil-square' => PaymentStatus::Draft,
                        'heroicon-m-clock' => PaymentStatus::Confirmed,
                        'heroicon-m-check-circle' => PaymentStatus::Reconciled,
                        'heroicon-m-x-circle' => PaymentStatus::Canceled,
                    ])
                    ->searchable(),

                // Payment Date (important for chronological sorting)
                TextColumn::make('payment_date')
                    ->label(__('payment.date'))
                    ->date()
                    ->sortable(),

                // Amount (critical financial information)
                MoneyColumn::make('amount')
                    ->label(__('payment.amount'))
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                // Currency (important for multi-currency)
                TextColumn::make('currency.code')
                    ->label(__('payment.currency'))
                    ->badge()
                    ->toggleable(),

                // Journal (important for categorization)
                TextColumn::make('journal.name')
                    ->label(__('payment.journal'))
                    ->sortable()
                    ->toggleable(),

                // Company (for multi-company setups)
                TextColumn::make('company.name')
                    ->label(__('payment.company'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('payment.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('payment.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (Payment $record) {
                        app(PaymentService::class)->delete($record);
                    })
                    ->visible(fn(Payment $record): bool => $record->status === PaymentStatus::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(fn(Payment $record) => app(PaymentService::class)->delete($record));
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InvoicesRelationManager::class,
            VendorBillsRelationManager::class,
            JournalEntriesRelationManager::class,
            BankStatementLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}
