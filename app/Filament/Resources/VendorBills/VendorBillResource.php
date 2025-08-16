<?php

namespace App\Filament\Resources\VendorBills;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Enums\Partners\PartnerType;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use App\Enums\Products\ProductType;
use App\Enums\Accounting\TaxType;
use App\Enums\Accounting\AccountType;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\VendorBills\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\VendorBills\RelationManagers\AdjustmentDocumentsRelationManager;
use App\Filament\Resources\VendorBills\Pages\ListVendorBills;
use App\Filament\Resources\VendorBills\Pages\CreateVendorBill;
use App\Filament\Resources\VendorBills\Pages\EditVendorBill;
use App\Models\Tax;
use Filament\Forms;
use Filament\Tables;
use App\Models\Account;
use App\Models\Company;
use App\Models\Product;
use App\Models\Partner;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\VendorBill;
use Filament\Tables\Table;
use App\Models\AnalyticAccount;
use App\Rules\NotInLockedPeriod;
use Filament\Resources\Resource;
use App\Enums\Shared\PaymentState;
use App\Services\VendorBillService;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use App\Enums\Purchases\VendorBillStatus;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\VendorBillResource\Pages;
use App\Filament\Support\TranslatableSelect;

class VendorBillResource extends Resource
{
    protected static ?string $model = VendorBill::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 1;

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
            Section::make()
                ->schema([
                    TranslatableSelect::standard(
                        'vendor_id',
                        \App\Models\Partner::class,
                        ['name', 'email', 'contact_person'],
                        __('vendor_bill.vendor')
                    )
                        ->required()
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
                        ->createOptionAction(function (\Filament\Actions\Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),
                    TranslatableSelect::make('currency_id', \App\Models\Currency::class, __('vendor_bill.currency'))
                        ->required()
                        ->live()
                        ->default(fn() => \Filament\Facades\Filament::getTenant()?->currency_id)
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                            if ($state) {
                                $currency = \App\Models\Currency::find($state);
                                $company = \Filament\Facades\Filament::getTenant();

                                if ($currency && $company && $currency->id !== $company->currency_id) {
                                    // Get latest exchange rate
                                    $latestRate = \App\Models\CurrencyRate::getLatestRate($currency->id);
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
                        ->createOptionAction(function (\Filament\Actions\Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),

                    TextInput::make('current_exchange_rate')
                        ->label(__('vendor_bill.current_exchange_rate'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(function (callable $get) {
                            $currencyId = $get('currency_id');
                            $company = \Filament\Facades\Filament::getTenant();
                            return $currencyId && $company && $currencyId != $company->currency_id;
                        })
                        ->helperText(__('vendor_bill.exchange_rate_helper')),
                    TextInput::make('bill_reference')
                        ->label(__('vendor_bill.bill_reference'))
                        ->required()
                        ->maxLength(255),
                    DatePicker::make('bill_date')
                        ->label(__('vendor_bill.bill_date'))
                        ->default(now())
                        ->required()
                        ->rules([new NotInLockedPeriod()]),
                    DatePicker::make('accounting_date')
                        ->default(now())
                        ->label(__('vendor_bill.accounting_date'))
                        ->required()
                        ->rules([new NotInLockedPeriod()]),
                    DatePicker::make('due_date')
                        ->label(__('vendor_bill.due_date')),
                    Select::make('status')
                        ->label(__('vendor_bill.status'))
                        ->options(
                            collect(VendorBillStatus::cases())
                                ->mapWithKeys(fn(VendorBillStatus $status) => [$status->value => $status->label()])
                        )
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(2),

            Section::make(__('vendor_bill.lines'))
                ->schema([
                    Repeater::make('lines')
                        ->label(__('vendor_bill.lines'))
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->disabled(fn(?VendorBill $record) => $record ? $record->status !== VendorBillStatus::Draft : false)
                        ->deletable(fn(?VendorBill $record) => $record === null || $record->status === VendorBillStatus::Draft)
                        ->schema([
                            TranslatableSelect::standard(
                                'product_id',
                                \App\Models\Product::class,
                                ['name', 'sku', 'description'],
                                __('vendor_bill.product')
                            )
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('description', $product->name);
                                            $set('unit_price', $product->unit_price);
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
                                        ->options(
                                            collect(ProductType::cases())
                                                ->mapWithKeys(fn (ProductType $type) => [$type->value => $type->label()])
                                        ),
                                    Textarea::make('description')
                                        ->label(__('product.description'))
                                        ->rows(3),
                                    Toggle::make('is_active')
                                        ->label(__('product.is_active'))
                                        ->default(true),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_product'))
                                ->createOptionAction(function (\Filament\Actions\Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(2),
                            TextInput::make('description')
                                ->label(__('vendor_bill.description'))
                                ->maxLength(255)
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('quantity')
                                ->label(__('vendor_bill.quantity'))
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->columnSpan(1),
                            MoneyInput::make('unit_price')
                                ->label(__('vendor_bill.unit_price'))
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(1),
                            TranslatableSelect::make('tax_id', \App\Models\Tax::class, __('vendor_bill.tax'))
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
                                        ->options(collect(TaxType::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                                        ->required(),
                                    Toggle::make('is_active')
                                        ->label(__('tax.is_active'))
                                        ->default(true),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_tax'))
                                ->createOptionAction(function (\Filament\Actions\Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(1),
                            TranslatableSelect::withFormatter(
                                'expense_account_id',
                                \App\Models\Account::class,
                                fn($account) => [$account->id => $account->getTranslatedLabel('name') . ' (' . $account->code . ')'],
                                __('vendor_bill.expense_account')
                            )
                                ->required()
                                ->createOptionForm([
                                    Select::make('company_id')
                                        ->label(__('account.company'))
                                        ->relationship('company', 'name')
                                        ->required(),
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
                                ->createOptionAction(function (\Filament\Actions\Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(2),
                            TranslatableSelect::standard(
                                'analytic_account_id',
                                \App\Models\AnalyticAccount::class,
                                ['name'],
                                __('vendor_bill.analytic_account')
                            )
                                ->createOptionForm([
                                    Select::make('company_id')
                                        ->relationship('company', 'name')
                                        ->label(__('analytic_account.company'))
                                        ->required(),
                                    TranslatableSelect::make('currency_id', \App\Models\Currency::class, __('analytic_account.currency')),
                                    TextInput::make('name')
                                        ->label(__('analytic_account.name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('reference')
                                        ->label(__('analytic_account.reference'))
                                        ->maxLength(255),
                                    Toggle::make('is_active')
                                        ->label(__('analytic_account.is_active'))
                                        ->default(true),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_analytic_account'))
                                ->createOptionAction(function (\Filament\Actions\Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(2),
                        ])
                        ->columns(5)
                        ->columnSpanFull(),
                ]),

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
                ->columns(3)
                ->visible(fn (?VendorBill $record) => $record && ($record->exchange_rate_at_creation || $record->total_amount_company_currency)),

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
                        ->disabled(fn(?VendorBill $record) => $record ? $record->status !== VendorBillStatus::Draft : false)
                        ->helperText(__('vendor_bill.attachments_helper'))
                        ->downloadable()
                        ->openable()
                        ->deletable(fn(?VendorBill $record) => $record === null || $record->status === VendorBillStatus::Draft)
                        ->reorderable(),
                ])
                ->collapsible()
                ->collapsed(fn(?VendorBill $record) => $record && $record->attachments()->count() === 0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('vendor_bill.company'))
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->label(__('vendor_bill.vendor'))
                    ->sortable(),
                TextColumn::make('bill_reference')
                    ->label(__('vendor_bill.bill_reference'))
                    ->searchable(),
                TextColumn::make('bill_date')
                    ->label(__('vendor_bill.bill_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('vendor_bill.status'))
                    ->colors([
                        'success' => VendorBillStatus::Posted,
                        'danger' => VendorBillStatus::Cancelled,
                        'warning' => VendorBillStatus::Draft,
                    ])
                    ->searchable(),
                TextColumn::make('paymentState')
                    ->label('Payment State')
                    // `formatStateUsing` receives the Enum object from the accessor.
                    // We then call the `label()` method on it.
                    ->formatStateUsing(fn(PaymentState $state): string => $state->label())
                    ->badge()
                    // The `color` closure also receives the Enum object directly.
                    ->color(fn(PaymentState $state): string => $state->color()),
                MoneyColumn::make('total_amount')
                    ->label(__('vendor_bill.total_amount'))
                    ->sortable(),
                MoneyColumn::make('total_tax')
                    ->label(__('vendor_bill.total_tax'))
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
                TextColumn::make('posted_at')
                    ->label(__('vendor_bill.posted_at'))
                    ->dateTime()
                    ->sortable(),
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
