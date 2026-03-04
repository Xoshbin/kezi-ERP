<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Schemas;

use Brick\Money\Money;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Kezi\Accounting\Enums\Accounting\TaxType;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Enums\Incoterm;
use Kezi\Foundation\Filament\Forms\Components\ExchangeRateInput;
use Kezi\Foundation\Filament\Forms\Components\PartnerSelectField;
use Kezi\Foundation\Filament\Helpers\DocumentAttachmentsHelper;
use Kezi\Foundation\Filament\Helpers\DocumentTotalsHelper;
use Kezi\Foundation\Models\Currency;
use Kezi\Product\Filament\Forms\Components\ProductSelectField;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Models\PurchaseOrder;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('purchase::purchase_orders.sections.vendor_currency_info'))
                    ->description(__('purchase::purchase_orders.sections.vendor_currency_info_description'))
                    ->schema([
                        PartnerSelectField::make('vendor_id')
                            ->label(__('purchase::purchase_orders.fields.vendor'))
                            ->required()
                            ->columnSpan(2),

                        \Kezi\Foundation\Filament\Forms\Components\CurrencySelectField::make('currency_id')
                            ->required()
                            ->columnSpan(2),

                        ExchangeRateInput::make('exchange_rate_at_creation')
                            ->columnSpan(1),

                        Select::make('incoterm')
                            ->label(__('purchase::purchase_orders.fields.incoterm'))
                            ->options(Incoterm::class)
                            ->searchable()
                            ->preload(),

                        TextInput::make('incoterm_location')
                            ->label(__('purchase::purchase_orders.fields.incoterm_location'))
                            ->maxLength(255),

                        Select::make('delivery_location_id')
                            ->label(__('purchase::purchase_orders.fields.delivery_location'))
                            ->relationship('deliveryLocation', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make(__('purchase::purchase_orders.sections.order_details'))
                    ->description(__('purchase::purchase_orders.sections.order_details_description'))
                    ->schema([
                        Hidden::make('company_id')
                            ->default(fn () => Filament::getTenant()?->getKey()),

                        Hidden::make('created_by_user_id')
                            ->default(fn () => Auth::id()),

                        TextInput::make('po_number')
                            ->label(__('purchase::purchase_orders.fields.po_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(__('purchase::purchase_orders.help.po_number')),

                        Select::make('status')
                            ->label(__('purchase::purchase_orders.fields.status'))
                            ->options(function (?string $operation, ?PurchaseOrder $record) {
                                // In create mode, show all statuses
                                if ($operation === 'create') {
                                    return PurchaseOrderStatus::class;
                                }

                                // In edit mode, show only valid forward transitions
                                if ($operation === 'edit' && $record) {
                                    $validTransitions = $record->status->getValidTransitions();
                                    $options = [];
                                    foreach ($validTransitions as $status) {
                                        $options[$status->value] = $status->label();
                                    }

                                    return $options;
                                }

                                return PurchaseOrderStatus::class;
                            })
                            ->default(PurchaseOrderStatus::Draft)
                            ->disabled(function (?string $operation, ?PurchaseOrder $record) {
                                // Always editable in create mode
                                if ($operation === 'create') {
                                    return false;
                                }

                                // In edit mode, allow editing if status allows flexibility
                                if ($operation === 'edit' && $record) {
                                    // Allow editing for active statuses that might need manual adjustment
                                    return ! in_array($record->status, [
                                        PurchaseOrderStatus::Draft,
                                        PurchaseOrderStatus::Sent,
                                        PurchaseOrderStatus::Confirmed,
                                        PurchaseOrderStatus::ToReceive,
                                        PurchaseOrderStatus::PartiallyReceived,
                                        PurchaseOrderStatus::FullyReceived,
                                        PurchaseOrderStatus::ToBill,
                                        PurchaseOrderStatus::PartiallyBilled,
                                    ]);
                                }

                                return true;
                            })
                            ->helperText(function (?string $operation, ?PurchaseOrder $record) {
                                if ($operation === 'edit' && $record) {
                                    $messages = [];

                                    // Bill creation status message
                                    if ($record->canCreateBill()) {
                                        $messages[] = __('purchase::purchase_orders.help.status_can_create_bill');
                                    } elseif ($record->hasBills()) {
                                        $messages[] = __('purchase::purchase_orders.help.status_bills_already_exist');
                                    } else {
                                        $messages[] = __('purchase::purchase_orders.help.status_cannot_create_bill');
                                    }

                                    // Forward-only transition message
                                    $messages[] = __('purchase::purchase_orders.help.status_forward_only');

                                    return implode(' ', $messages);
                                }

                                return null;
                            })
                            ->required(),

                        DatePicker::make('po_date')
                            ->label(__('purchase::purchase_orders.fields.po_date'))
                            ->default(now())
                            ->required(),

                        DatePicker::make('expected_delivery_date')
                            ->label(__('purchase::purchase_orders.fields.expected_delivery_date'))
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                // Update all line items' expected_delivery_date to match the header
                                $lines = $get('lines') ?? [];
                                if (! empty($lines)) {
                                    foreach ($lines as $uuid => $line) {
                                        $lines[$uuid]['expected_delivery_date'] = $state;
                                    }
                                    $set('lines', $lines);
                                }
                            }),

                        TextInput::make('reference')
                            ->label(__('purchase::purchase_orders.fields.reference'))
                            ->helperText(__('purchase::purchase_orders.help.reference'))
                            ->columnSpan(2),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make(__('purchase::purchase_orders.sections.line_items'))
                    ->description(__('purchase::purchase_orders.sections.line_items_description'))
                    ->schema([
                        Repeater::make('lines')
                            ->label(__('purchase::purchase_orders.fields.lines'))
                            ->table([
                                TableColumn::make(__('purchase::purchase_orders.fields.product'))->width('18%'),
                                TableColumn::make(__('purchase::purchase_orders.fields.description'))->width('15%'),
                                TableColumn::make(__('purchase::purchase_orders.fields.quantity'))->width('8%'),
                                TableColumn::make(__('purchase::purchase_orders.fields.unit_price'))->width('12%'),
                                TableColumn::make(__('purchase::purchase_orders.fields.tax'))->width('12%'),
                                TableColumn::make(__('foundation::enums.shipping_cost_type.freight'))->width('12%'),
                                TableColumn::make(__('purchase::purchase_orders.fields.expected_delivery_date'))->width('12%'),
                                TableColumn::make(__('purchase::purchase_orders.fields.notes'))->width('20%'),
                            ])
                            ->live()
                            ->reorderable(true)
                            ->minItems(1)
                            ->schema([
                                ProductSelectField::make('product_id')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('description', $product->description ?: $product->name);

                                                $exchangeRate = (float) $get('../../exchange_rate_at_creation') ?: 1.0;

                                                // Product price is in base currency
                                                $unitPrice = $product->unit_price;

                                                // Calculate price in foreign currency: Base Price / Exchange Rate
                                                if ($exchangeRate > 0) {
                                                    $amountDecimal = \Brick\Math\BigDecimal::of($unitPrice instanceof Money ? $unitPrice->getAmount() : (filled($unitPrice) ? $unitPrice : 0));
                                                    $convertedPrice = $amountDecimal->dividedBy($exchangeRate, 6, \Brick\Math\RoundingMode::HALF_UP)->stripTrailingZeros();
                                                    $set('unit_price', (string) $convertedPrice);
                                                } else {
                                                    $set('unit_price', '0');
                                                }

                                                // Auto-detect shipping cost type
                                                $name = strtolower($product->name);
                                                if (str_contains($name, 'freight') || str_contains($name, 'shipping')) {
                                                    $set('shipping_cost_type', \Kezi\Foundation\Enums\ShippingCostType::Freight);
                                                } elseif (str_contains($name, 'insurance')) {
                                                    $set('shipping_cost_type', \Kezi\Foundation\Enums\ShippingCostType::Insurance);
                                                }

                                                // Auto-populate purchase tax
                                                $defaultTax = $product->purchaseTaxes()->first();
                                                if ($defaultTax) {
                                                    $set('tax_id', $defaultTax->id);
                                                }
                                            }
                                        }
                                    })
                                    ->columnSpan(3),

                                TextInput::make('description')
                                    ->label(__('purchase::purchase_orders.fields.description'))
                                    ->maxLength(255)
                                    ->required()
                                    ->columnSpan(4),

                                TextInput::make('quantity')
                                    ->label(__('purchase::purchase_orders.fields.quantity'))
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->live(onBlur: true)
                                    ->extraInputAttributes(['onclick' => 'this.select()'])
                                    ->columnSpan(2),

                                \Kezi\Foundation\Filament\Forms\Components\MoneyInput::make('unit_price')
                                    ->label(__('purchase::purchase_orders.fields.unit_price'))
                                    ->currencyField('../../currency_id')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->columnSpan(3),

                                Select::make('tax_id')
                                    ->options(fn () => Tax::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
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
                                    ->createOptionModalHeading(__('foundation::common.modal_title_create_tax'))
                                    ->createOptionAction(function (Action $action) {
                                        return $action->modalWidth('lg');
                                    })
                                    ->columnSpan(3),

                                DatePicker::make('expected_delivery_date')
                                    ->label(__('purchase::purchase_orders.fields.expected_delivery_date'))
                                    ->default(fn (callable $get) => $get('../../expected_delivery_date'))
                                    ->columnSpan(3),
                                Select::make('shipping_cost_type')
                                    ->label(__('foundation::enums.shipping_cost_type.freight'))
                                    ->options(\Kezi\Foundation\Enums\ShippingCostType::class)
                                    ->placeholder(__('foundation::enums.shipping_cost_type.none'))
                                    ->nullable()
                                    ->columnSpan(3),
                                Textarea::make('notes')
                                    ->label(__('purchase::purchase_orders.fields.notes'))
                                    ->rows(2)
                                    ->columnSpan(3),
                            ])
                            ->columns(18),
                    ])->columnSpanFull(),

                DocumentTotalsHelper::make(
                    linesKey: 'lines',
                    translationPrefix: 'purchase::purchase_orders.fields',
                    totalsLabel: __('purchase::purchase_orders.sections.totals'),
                    subtotalLabel: __('purchase::purchase_orders.fields.subtotal'),
                    taxLabel: __('purchase::purchase_orders.fields.total_tax'),
                    totalLabel: __('purchase::purchase_orders.fields.total_amount'),
                    companyCurrencyTotalLabel: __('purchase::purchase_orders.fields.total_amount_company_currency')
                )->collapsible()->collapsed(false),

                Section::make(__('purchase::purchase_orders.sections.notes'))
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('purchase::purchase_orders.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('terms_and_conditions')
                            ->label(__('purchase::purchase_orders.fields.terms_and_conditions'))
                            ->helperText(__('purchase::purchase_orders.help.terms_and_conditions'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                DocumentAttachmentsHelper::makeSection(
                    directory: 'purchase-orders',
                    disabledCallback: fn (?PurchaseOrder $record) => $record && $record->status !== PurchaseOrderStatus::Draft,
                    deletableCallback: fn (?PurchaseOrder $record) => $record === null || $record->status === PurchaseOrderStatus::Draft
                ),
            ]);
    }
}
