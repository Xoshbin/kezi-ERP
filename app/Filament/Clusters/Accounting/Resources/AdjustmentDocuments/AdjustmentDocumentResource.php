<?php

namespace App\Filament\Clusters\Accounting\Resources\AdjustmentDocuments;

use App\Enums\Accounting\AccountType;
use App\Enums\Adjustments\AdjustmentDocumentStatus;
use App\Enums\Adjustments\AdjustmentDocumentType;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages\CreateAdjustmentDocument;
use App\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages\EditAdjustmentDocument;
use App\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages\ListAdjustmentDocuments;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Support\TranslatableSelect;
use App\Models\Account;
use App\Models\AdjustmentDocument;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tax;
use App\Models\VendorBill;
use App\Rules\NotInLockedPeriod;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdjustmentDocumentResource extends Resource
{
    protected static ?string $model = AdjustmentDocument::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.core_accounting');
    }

    public static function getModelLabel(): string
    {
        return __('adjustment_document.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('adjustment_document.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('adjustment_document.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('adjustment_document.document_information'))
                ->description(__('adjustment_document.document_information_description'))
                ->schema([
                    TranslatableSelect::make('currency_id', Currency::class, __('adjustment_document.currency'))
                        ->required()
                        ->live()
                        ->columnSpan(2)
                        ->default(function (): ?int {
                            $tenant = Filament::getTenant();
                            return $tenant instanceof \App\Models\Company ? $tenant->currency_id : null;
                        })
                        ->disabled(fn (Get $get): bool => ! empty($get('original_invoice_id')) || ! empty($get('original_vendor_bill_id')))
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
                    Select::make('type')
                        ->label(__('adjustment_document.adjustment_type'))
                        ->options(
                            collect(AdjustmentDocumentType::cases())
                                ->mapWithKeys(fn (AdjustmentDocumentType $type) => [$type->value => $type->label()])
                        )
                        ->required()
                        ->searchable()
                        ->columnSpan(2),
                    TextInput::make('reference_number')
                        ->label(__('adjustment_document.reference_number'))
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g., ADJ-2024-001')
                        ->columnSpan(2),
                    DatePicker::make('date')
                        ->label(__('adjustment_document.adjustment_date'))
                        ->required()
                        ->rules([new NotInLockedPeriod])
                        ->default(now())
                        ->native(false)
                        ->columnSpan(1),
                    Select::make('status')
                        ->label(__('adjustment_document.status'))
                        ->options(collect(AdjustmentDocumentStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                        ->disabled()
                        ->dehydrated(false)
                        ->default(AdjustmentDocumentStatus::Draft->value)
                        ->columnSpan(1),
                    Textarea::make('reason')
                        ->label(__('adjustment_document.reason_for_adjustment'))
                        ->required()
                        ->placeholder('Describe the reason for this adjustment...')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('adjustment_document.link_to_original_document'))
                ->description(__('adjustment_document.link_to_original_document_description'))
                ->schema([
                    Select::make('document_link_type')
                        ->label(__('adjustment_document.document_type_to_adjust'))
                        ->options([
                            'invoice' => __('adjustment_document.invoice'),
                            'vendor_bill' => __('adjustment_document.vendor_bill'),
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn (Set $set) => [$set('original_invoice_id', null), $set('original_vendor_bill_id', null)])
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Get $get, Set $set) {
                            if ($get('original_invoice_id')) {
                                $set('document_link_type', 'invoice');
                            } elseif ($get('original_vendor_bill_id')) {
                                $set('document_link_type', 'vendor_bill');
                            }
                        })
                        ->placeholder('Select document type to link...'),
                    Select::make('original_invoice_id')
                        ->label('Original Invoice')
                        ->searchable()
                        ->preload()
                        ->relationship(
                            'originalInvoice',
                            'invoice_number',
                            fn ($query) => $query->posted()->with('customer')
                        )
                        ->getOptionLabelUsing(function ($value): ?string {
                            $invoice = Invoice::posted()->with('customer')->find($value);
                            if (! $invoice) {
                                return null;
                            }
                            // Ensure we have a single Invoice model, not a collection
                            if ($invoice instanceof \Illuminate\Database\Eloquent\Collection) {
                                $invoice = $invoice->first();
                            }
                            if (!$invoice) {
                                return null;
                            }

                            return $invoice->invoice_number.' - '.$invoice->customer->name;
                        })
                        ->visible(fn (Get $get) => $get('document_link_type') === 'invoice')
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $invoice = Invoice::find($state);
                                // Ensure we have a single Invoice model, not a collection
                                if ($invoice instanceof \Illuminate\Database\Eloquent\Collection) {
                                    $invoice = $invoice->first();
                                }
                                $set('currency_id', $invoice?->currency_id);
                            }
                        })
                        ->placeholder('Search for an invoice...'),
                    Select::make('original_vendor_bill_id')
                        ->label('Original Vendor Bill')
                        ->searchable()
                        ->preload()
                        ->relationship('originalVendorBill', 'bill_reference', fn ($query) => $query->posted())
                        ->visible(fn (Get $get) => $get('document_link_type') === 'vendor_bill')
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $bill = VendorBill::find($state);
                                // Ensure we have a single VendorBill model, not a collection
                                if ($bill instanceof \Illuminate\Database\Eloquent\Collection) {
                                    $bill = $bill->first();
                                }
                                $set('currency_id', $bill?->currency_id);
                            }
                        })
                        ->placeholder('Search for a vendor bill...'),
                ])
                ->columnSpanFull(),

            Section::make(__('adjustment_document.line_items'))
                ->description(__('adjustment_document.line_items_description'))
                ->schema([
                    Repeater::make('lines')
                        ->label('')
                        ->table([
                            TableColumn::make(__('adjustment_document.product'))->width('20%'),
                            TableColumn::make(__('adjustment_document.description'))->width('20%'),
                            TableColumn::make(__('adjustment_document.qty'))->width('10%'),
                            TableColumn::make('Price')->width('15%'),
                            TableColumn::make('Tax')->width('20%'),
                            TableColumn::make('Account')->width('15%'),
                        ])
                        ->live()
                        ->reorderable(false)
                        ->minItems(1)
                        ->schema([
                            TranslatableSelect::standard(
                                'product_id',
                                Product::class,
                                ['name', 'sku', 'description'],
                                __('adjustment_document.product')
                            )
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $product = Product::find($state);
                                        // Ensure we have a single Product model, not a collection
                                        if ($product instanceof \Illuminate\Database\Eloquent\Collection) {
                                            $product = $product->first();
                                        }
                                        if ($product) {
                                            $set('description', $product->name);
                                            $set('unit_price', $product->unit_price);
                                            $set('account_id', $product->income_account_id);
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
                                ->label(__('adjustment_document.description'))
                                ->maxLength(255)
                                ->required()
                                ->columnSpan(4),
                            TextInput::make('quantity')
                                ->label(__('adjustment_document.qty'))
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->columnSpan(2),
                            MoneyInput::make('unit_price')
                                ->label('Price')
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(3),
                            TranslatableSelect::make('tax_id', Tax::class, 'Tax')
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
                            TranslatableSelect::withFormatter(
                                'account_id',
                                Account::class,
                                fn ($account) => [$account->id => $account->getTranslatedLabel('name').' ('.$account->code.')'],
                                'Account'
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
                                ->createOptionAction(function (Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
                                ->columnSpan(3),
                        ])
                        ->columns(18),
                ])
                ->columnSpanFull(),
        ]);
    }

    // table(), getRelations(), and getPages() methods are unchanged.
    // ...
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable()
                    ->copyMessage('Reference copied!')
                    ->icon('heroicon-o-hashtag'),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->icon('heroicon-o-building-office'),
                TextColumn::make('type')
                    ->label('Type')
                    ->searchable()
                    ->badge()
                    ->color(fn (AdjustmentDocumentType $state): string => match ($state->value) {
                        'credit_note' => 'success',
                        'debit_note' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (AdjustmentDocumentType $state): string => ucfirst(str_replace('_', ' ', $state->value))),
                TextColumn::make('date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),
                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->currency->code)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (AdjustmentDocumentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (AdjustmentDocumentStatus $state): string => match ($state) {
                        AdjustmentDocumentStatus::Draft => 'warning',
                        AdjustmentDocumentStatus::Posted => 'success',
                        AdjustmentDocumentStatus::Cancelled => 'danger',
                    })
                    ->icon(fn (AdjustmentDocumentStatus $state): string => match ($state) {
                        AdjustmentDocumentStatus::Draft => 'heroicon-m-pencil-square',
                        AdjustmentDocumentStatus::Posted => 'heroicon-m-check-circle',
                        AdjustmentDocumentStatus::Cancelled => 'heroicon-m-x-circle',
                    })
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft',
                        'Posted' => 'Posted',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                SelectFilter::make('type')
                    ->options(
                        collect(AdjustmentDocumentType::cases())
                            ->mapWithKeys(fn (AdjustmentDocumentType $type) => [$type->value => $type->label()])
                    )
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading(__('filament.empty_states.no_records_found'))
            ->emptyStateDescription(__('filament.empty_states.create_first_record'))
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdjustmentDocuments::route('/'),
            'create' => CreateAdjustmentDocument::route('/create'),
            'edit' => EditAdjustmentDocument::route('/{record}/edit'),
        ];
    }
}
