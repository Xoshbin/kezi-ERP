<?php

namespace App\Filament\Resources;

use App\Models\Tax;
use Filament\Forms;
use Filament\Tables;
use App\Models\Account;
use App\Models\Company;
use App\Models\Product;
use App\Models\Partner;
use App\Models\Currency;
use App\Models\Journal;
use Filament\Forms\Form;
use App\Models\VendorBill;
use Filament\Tables\Table;
use App\Models\AnalyticAccount;
use App\Rules\NotInLockedPeriod;
use Filament\Resources\Resource;
use App\Enums\Shared\PaymentState;
use App\Services\VendorBillService;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use App\Enums\Purchases\VendorBillStatus;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\VendorBillResource\Pages;

class VendorBillResource extends Resource
{
    protected static ?string $model = VendorBill::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

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

    public static function form(Form $form): Form
    {
        $company = Company::first();

        return $form->schema([
            Section::make()
                ->schema([

                    Forms\Components\Select::make('vendor_id')
                        ->relationship('vendor', 'name')
                        ->label(__('vendor_bill.vendor'))
                        ->required()
                        ->searchable()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label(__('partner.name'))
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Select::make('type')
                                ->label(__('partner.type'))
                                ->required()
                                ->options(
                                    collect(\App\Enums\Partners\PartnerType::cases())
                                        ->mapWithKeys(fn (\App\Enums\Partners\PartnerType $type) => [$type->value => $type->label()])
                                ),
                            Forms\Components\TextInput::make('contact_person')
                                ->label(__('partner.contact_person'))
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->label(__('partner.email'))
                                ->email()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('phone')
                                ->label(__('partner.phone'))
                                ->maxLength(255),
                            Forms\Components\Textarea::make('address')
                                ->label(__('partner.address'))
                                ->columnSpanFull(),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_partner'))
                        ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),
                    Forms\Components\Select::make('currency_id')
                        ->relationship('currency', 'name')
                        ->label(__('vendor_bill.currency'))
                        ->required()
                        ->live()
                        ->searchable()
                        ->default($company?->currency_id)
                        ->createOptionForm([
                            Forms\Components\TextInput::make('code')
                                ->label(__('currency.code'))
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('name')
                                ->label(__('currency.name'))
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('symbol')
                                ->label(__('currency.symbol'))
                                ->required()
                                ->maxLength(5),
                            Forms\Components\TextInput::make('exchange_rate')
                                ->label(__('currency.exchange_rate'))
                                ->required()
                                ->numeric()
                                ->default(1),
                            Forms\Components\Toggle::make('is_active')
                                ->label(__('currency.is_active'))
                                ->required()
                                ->default(true),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_currency'))
                        ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),
                    Forms\Components\TextInput::make('bill_reference')
                        ->label(__('vendor_bill.bill_reference'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\DatePicker::make('bill_date')
                        ->label(__('vendor_bill.bill_date'))
                        ->default(now())
                        ->required()
                        ->rules([new NotInLockedPeriod($company)]),
                    Forms\Components\DatePicker::make('accounting_date')
                        ->default(now())
                        ->label(__('vendor_bill.accounting_date'))
                        ->required()
                        ->rules([new NotInLockedPeriod()]),
                    Forms\Components\DatePicker::make('due_date')
                        ->label(__('vendor_bill.due_date')),
                    Forms\Components\Select::make('status')
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
                    Forms\Components\Repeater::make('lines')
                        ->label(__('vendor_bill.lines'))
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->disabled(fn(?VendorBill $record) => $record ? $record->status !== VendorBillStatus::Draft : false)
                        ->deletable(fn(?VendorBill $record) => $record === null || $record->status === VendorBillStatus::Draft)
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label(__('vendor_bill.product'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array => Product::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->name)
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
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('product.name'))
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('sku')
                                        ->label(__('product.sku'))
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('type')
                                        ->label(__('product.type'))
                                        ->required()
                                        ->options(
                                            collect(\App\Enums\Products\ProductType::cases())
                                                ->mapWithKeys(fn (\App\Enums\Products\ProductType $type) => [$type->value => $type->label()])
                                        ),
                                    Forms\Components\Textarea::make('description')
                                        ->label(__('product.description'))
                                        ->rows(3),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label(__('product.is_active'))
                                        ->default(true),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_product'))
                                ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('description')
                                ->label(__('vendor_bill.description'))
                                ->maxLength(255)
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')
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
                            Forms\Components\Select::make('tax_id')
                                ->label(__('vendor_bill.tax'))
                                ->searchable()
                                ->getSearchResultsUsing(
                                    fn(string $search): array =>
                                    Tax::whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.' . app()->getLocale() . '"))) LIKE ?', ['%' . strtolower($search) . '%'])
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($tax) => [$tax->id => $tax->getTranslation('name', app()->getLocale())])
                                        ->toArray()
                                )
                                ->getOptionLabelUsing(fn($value): ?string => Tax::find($value)?->getTranslation('name', app()->getLocale()))
                                ->createOptionForm([
                                    Forms\Components\Select::make('tax_account_id')
                                        ->relationship('taxAccount', 'name')
                                        ->label(__('tax.tax_account'))
                                        ->required(),
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('tax.name'))
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('rate')
                                        ->label(__('tax.rate'))
                                        ->required()
                                        ->numeric(),
                                    Forms\Components\Select::make('type')
                                        ->label(__('tax.type'))
                                        ->options(collect(\App\Enums\Accounting\TaxType::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                                        ->required(),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label(__('tax.is_active'))
                                        ->default(true),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_tax'))
                                ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(1),
                            Forms\Components\Select::make('expense_account_id')
                                ->label(__('vendor_bill.expense_account'))
                                ->searchable()
                                ->getSearchResultsUsing(
                                    fn(string $search): array =>
                                    Account::whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.' . app()->getLocale() . '"))) LIKE ?', ['%' . strtolower($search) . '%'])
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($account) => [$account->id => $account->getTranslation('name', app()->getLocale())])
                                        ->toArray()
                                )
                                ->getOptionLabelUsing(fn($value): ?string => Account::find($value)?->getTranslation('name', app()->getLocale()))
                                ->required()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('code')
                                        ->label(__('account.code'))
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('account.name'))
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('type')
                                        ->label(__('account.type'))
                                        ->required()
                                        ->options(
                                            collect(\App\Enums\Accounting\AccountType::cases())
                                                ->mapWithKeys(fn (\App\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                        )
                                        ->searchable(),
                                    Forms\Components\Toggle::make('is_deprecated')
                                        ->label(__('account.is_deprecated'))
                                        ->default(false),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_account'))
                                ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(2),
                            Forms\Components\Select::make('analytic_account_id')
                                ->label(__('vendor_bill.analytic_account'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array => AnalyticAccount::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => AnalyticAccount::find($value)?->name)
                                ->createOptionForm([
                                    Forms\Components\Select::make('currency_id')
                                        ->relationship('currency', 'name')
                                        ->label(__('analytic_account.currency')),
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('analytic_account.name'))
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('reference')
                                        ->label(__('analytic_account.reference'))
                                        ->maxLength(255),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label(__('analytic_account.is_active'))
                                        ->default(true),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_analytic_account'))
                                ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(2),
                        ])
                        ->columns(5)
                        ->columnSpanFull(),
                ]),

            Section::make(__('vendor_bill.attachments'))
                ->description(__('vendor_bill.attachments_description'))
                ->schema([
                    Forms\Components\FileUpload::make('attachments')
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
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('vendor_bill.company'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label(__('vendor_bill.vendor'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('bill_reference')
                    ->label(__('vendor_bill.bill_reference'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('bill_date')
                    ->label(__('vendor_bill.bill_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label(__('vendor_bill.status'))
                    ->colors([
                        'success' => VendorBillStatus::Posted,
                        'danger' => VendorBillStatus::Cancelled,
                        'warning' => VendorBillStatus::Draft,
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('paymentState')
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
                Tables\Columns\TextColumn::make('posted_at')
                    ->label(__('vendor_bill.posted_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('vendor_bill.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('vendor_bill.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\VendorBillLinesRelationManager::class,
            \App\Filament\Resources\VendorBillResource\RelationManagers\PaymentsRelationManager::class,
            \App\Filament\Resources\VendorBillResource\RelationManagers\AdjustmentDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorBills::route('/'),
            'create' => Pages\CreateVendorBill::route('/create'),
            'edit' => Pages\EditVendorBill::route('/{record}/edit'),
        ];
    }
}
