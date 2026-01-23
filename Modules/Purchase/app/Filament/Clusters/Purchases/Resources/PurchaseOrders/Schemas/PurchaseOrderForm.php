<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Schemas;

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
use Filament\Forms\Get;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Enums\Accounting\TaxType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Enums\Incoterm;
use Modules\Foundation\Enums\Partners\PartnerType;
use Modules\Foundation\Filament\Forms\Components\MoneyInput;
use Modules\Foundation\Filament\Helpers\DocumentAttachmentsHelper;
use Modules\Foundation\Models\Currency;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('purchase::purchase_orders.sections.basic_info'))
                    ->schema([
                        Hidden::make('company_id')
                            ->default(fn () => Auth::user()?->company_id),

                        Hidden::make('created_by_user_id')
                            ->default(fn () => Auth::id()),

                        Grid::make(2)
                            ->schema([
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
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('po_date')
                                    ->label(__('purchase::purchase_orders.fields.po_date'))
                                    ->default(now())
                                    ->required(),

                                TextInput::make('reference')
                                    ->label(__('purchase::purchase_orders.fields.reference'))
                                    ->helperText(__('purchase::purchase_orders.help.reference')),
                            ]),
                    ]),

                Section::make(__('purchase::purchase_orders.sections.vendor_details'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('vendor_id')
                                    ->label(__('purchase::purchase_orders.fields.vendor'))
                                    ->relationship('vendor', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required(),
                                        TextInput::make('email')
                                            ->email(),
                                        Select::make('type')
                                            ->label(__('purchase::partner.type'))
                                            ->options(
                                                collect(PartnerType::cases())
                                                    ->mapWithKeys(fn (PartnerType $type) => [$type->value => $type->label()])
                                            )
                                            ->default(PartnerType::Both)
                                            ->required(),
                                    ]),

                                Select::make('currency_id')
                                    ->options(fn () => Currency::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        $currencyId = $state;
                                        if (! $currencyId) {
                                            $set('exchange_rate_at_creation', 1);

                                            return;
                                        }

                                        $company = Filament::getTenant();
                                        if (! $company) {
                                            $company = Auth::user()?->company;
                                        }

                                        $currency = Currency::find($currencyId);
                                        $baseCurrency = $company?->currency;
                                        $newRate = 1.0;

                                        if ($currency && $baseCurrency) {
                                            if ($currency->id === $baseCurrency->id) {
                                                $set('exchange_rate_at_creation', 1);
                                                $newRate = 1.0;
                                            } else {
                                                $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);
                                                $rate = $service->getExchangeRate($currency, now(), $company) ?? $service->getLatestExchangeRate($currency, $company);
                                                $newRate = $rate ?? 1.0;
                                                $set('exchange_rate_at_creation', $newRate);
                                            }
                                        }

                                        // Recalculate prices for existing lines
                                        $lines = $get('lines') ?? [];
                                        if (! empty($lines)) {
                                            foreach ($lines as $uuid => $line) {
                                                if (isset($line['product_id'])) {
                                                    $product = Product::find($line['product_id']);
                                                    if ($product instanceof Product && $product->unit_price) {
                                                        // Get the underlying decimal amount from the Money object or value
                                                        $basePrice = $product->unit_price instanceof Money
                                                            ? $product->unit_price->getAmount()->toBigDecimal()
                                                            : $product->unit_price;

                                                        if ($newRate == 1.0) {
                                                            // Reverting to base currency: use original base price
                                                            $lines[$uuid]['unit_price'] = (string) $basePrice;
                                                        } else {
                                                            // Converting to foreign currency: Base / Rate
                                                            // We use standard division here. In a robust system, we might check for 0.
                                                            if ($newRate > 0) {
                                                                $converted = \Brick\Math\BigDecimal::of($basePrice)->dividedBy($newRate, 6, \Brick\Math\RoundingMode::HALF_UP);
                                                                $lines[$uuid]['unit_price'] = (string) $converted;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            $set('lines', $lines);
                                        }
                                    }),

                                TextInput::make('exchange_rate_at_creation')
                                    ->label(__('purchase::purchase_orders.fields.exchange_rate'))
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->live()
                                    ->visible(function (callable $get) {
                                        $currencyId = $get('currency_id');
                                        $company = Filament::getTenant();
                                        if (! $company) {
                                            $company = Auth::user()?->company;
                                        }

                                        return $currencyId && $company instanceof \App\Models\Company && $currencyId != $company->currency_id;
                                    })
                                    ->helperText(function (callable $get) {
                                        $currencyId = $get('currency_id');
                                        $company = Filament::getTenant();
                                        if (! $company) {
                                            $company = Auth::user()?->company;
                                        }

                                        if ($currencyId && $company instanceof \App\Models\Company && $currencyId !== $company->currency_id) {
                                            $currency = Currency::find($currencyId);
                                            if ($currency) {
                                                $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);
                                                $latestRate = $service->getExchangeRate($currency, now(), $company) ?? $service->getLatestExchangeRate($currency, $company);

                                                if ($latestRate) {
                                                    return __('purchase::purchase_orders.help.exchange_rate').' '.__('purchase::purchase_orders.help.current_rate', ['rate' => $latestRate]);
                                                }
                                            }
                                        }

                                        return __('purchase::purchase_orders.help.exchange_rate');
                                    }),

                                Select::make('incoterm')
                                    ->label(__('purchase::purchase_orders.fields.incoterm'))
                                    ->options(Incoterm::class)
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

                Section::make(__('purchase::purchase_orders.sections.delivery_info'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
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

                                Select::make('delivery_location_id')
                                    ->label(__('purchase::purchase_orders.fields.delivery_location'))
                                    ->relationship('deliveryLocation', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

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
                                TableColumn::make(__('Shipping Type'))->width('12%'),
                                TableColumn::make(__('purchase::purchase_orders.fields.expected_delivery_date'))->width('12%'),
                                TableColumn::make(__('purchase::purchase_orders.fields.notes'))->width('20%'),
                            ])
                            ->live()
                            ->reorderable(true)
                            ->minItems(1)
                            ->afterStateHydrated(function (callable $set, callable $get) {
                                // Calculate totals when form is first loaded (e.g., on edit page or page refresh)
                                static::updateTotals($set, $get);
                            })
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                static::updateTotals($set, $get);
                            })
                            ->schema([
                                Select::make('product_id')
                                    ->options(fn () => Product::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product instanceof Product) {
                                                $set('description', $product->description ?: $product->name);

                                                // Get the exchange rate from the form state
                                                $exchangeRate = (float) $get('../../exchange_rate_at_creation');
                                                if ($exchangeRate <= 0) {
                                                    $exchangeRate = 1;
                                                }

                                                // Product price is in base currency
                                                $unitPrice = $product->unit_price;

                                                if ($unitPrice instanceof Money) {
                                                    // Convert Money object to float
                                                    $amount = $unitPrice->getAmount()->toFloat();
                                                } else {
                                                    $amount = (float) $unitPrice;
                                                }

                                                // Calculate price in foreign currency: Base Price / Exchange Rate
                                                // Example: 2,500,000 IQD / 1500 Rate = 1666.66 USD
                                                // If rate is 1 (Base Currency), it stays standard.

                                                // Ensure we don't divide by zero
                                                if ($exchangeRate != 0) {
                                                    $convertedPrice = $amount / $exchangeRate;
                                                    // Format to appropriate decimals? MoneyInput handles it, but let's pass a clean float/string
                                                    $set('unit_price', (string) $convertedPrice);
                                                } else {
                                                    $set('unit_price', 0);
                                                }

                                                // Auto-detect shipping cost type
                                                $name = strtolower($product->name);
                                                if (str_contains($name, 'freight') || str_contains($name, 'shipping')) {
                                                    $set('shipping_cost_type', \Modules\Foundation\Enums\ShippingCostType::Freight);
                                                } elseif (str_contains($name, 'insurance')) {
                                                    $set('shipping_cost_type', \Modules\Foundation\Enums\ShippingCostType::Insurance);
                                                }

                                                // Auto-populate purchase tax
                                                $defaultTax = $product->purchaseTaxes()->first();
                                                if ($defaultTax) {
                                                    $set('tax_id', $defaultTax->id);
                                                }
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
                                                collect(\Modules\Product\Enums\Products\ProductType::cases())
                                                    ->mapWithKeys(fn (\Modules\Product\Enums\Products\ProductType $type) => [$type->value => $type->label()])
                                            ),
                                        Textarea::make('description')
                                            ->label(__('product.description'))
                                            ->rows(3),
                                        Toggle::make('is_active')
                                            ->label(__('product.is_active'))
                                            ->default(true),
                                        Select::make('default_inventory_account_id')
                                            ->label(__('product.default_inventory_account'))
                                            ->options(function () {
                                                return Account::where('company_id', Filament::getTenant()?->getKey())
                                                    ->where('is_deprecated', false)
                                                    ->pluck('name', 'id');
                                            })
                                            ->visible(fn ($get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                                            ->required(fn ($get) => $get('type') === \Modules\Product\Enums\Products\ProductType::Storable->value)
                                            ->searchable()
                                            ->preload(),
                                    ])
                                    ->createOptionModalHeading(__('common.modal_title_create_product'))
                                    ->createOptionAction(function (Action $action) {
                                        return $action->modalWidth('lg');
                                    })
                                    ->createOptionUsing(function (array $data): int {
                                        $data['company_id'] = Filament::getTenant()?->getKey();
                                        $product = Product::create($data);

                                        return $product->getKey();
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
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(2),

                                \Modules\Foundation\Filament\Forms\Components\MoneyInput::make('unit_price')
                                    ->label(__('purchase::purchase_orders.fields.unit_price'))
                                    ->currencyField('../../currency_id')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(3),

                                Select::make('tax_id')
                                    ->options(fn () => Tax::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->createOptionForm([
                                        Hidden::make('company_id')
                                            ->default(fn () => Filament::getTenant()?->getKey()),
                                        Select::make('tax_account_id')
                                            ->options(function () {
                                                return Account::where('company_id', Filament::getTenant()?->getKey())
                                                    ->where('is_deprecated', false)
                                                    ->pluck('name', 'id');
                                            })
                                            ->label(__('tax.tax_account'))
                                            ->searchable()
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
                                    ->createOptionUsing(function (array $data): int {
                                        $tax = Tax::create($data);

                                        return $tax->getKey();
                                    })
                                    ->createOptionModalHeading(__('common.modal_title_create_tax'))
                                    ->createOptionAction(function (Action $action) {
                                        return $action->modalWidth('lg');
                                    })
                                    ->columnSpan(3),

                                DatePicker::make('expected_delivery_date')
                                    ->label(__('purchase::purchase_orders.fields.expected_delivery_date'))
                                    ->default(fn (callable $get) => $get('../../expected_delivery_date'))
                                    ->columnSpan(3),
                                Select::make('shipping_cost_type')
                                    ->label(__('Shipping Type'))
                                    ->options(\Modules\Foundation\Enums\ShippingCostType::class)
                                    ->placeholder(__('None'))
                                    ->nullable()
                                    ->columnSpan(3),
                                Textarea::make('notes')
                                    ->label(__('purchase::purchase_orders.fields.notes'))
                                    ->rows(2)
                                    ->columnSpan(3),
                            ])
                            ->columns(18),
                    ])->columnSpanFull(),

                Section::make(__('purchase::purchase_orders.sections.totals'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                \Modules\Foundation\Filament\Forms\Components\MoneyInput::make('total_tax')
                                    ->label(__('purchase::purchase_orders.fields.total_tax'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),

                                \Modules\Foundation\Filament\Forms\Components\MoneyInput::make('total_amount')
                                    ->label(__('purchase::purchase_orders.fields.total_amount'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

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

    /**
     * Update the purchase order totals based on line items
     */
    public static function updateTotals(callable $set, callable $get): void
    {
        $lines = $get('lines') ?? [];
        $currencyId = $get('currency_id');

        if (! $currencyId || empty($lines)) {
            $set('total_amount', 0);
            $set('total_tax', 0);

            return;
        }

        // Get currency for calculations
        $currency = Currency::find($currencyId);
        if (! $currency) {
            return;
        }

        $totalAmount = 0;
        $totalTax = 0;

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $taxId = $line['tax_id'] ?? null;

            if ($quantity <= 0 || $unitPrice <= 0) {
                continue;
            }

            // Calculate line subtotal
            $lineSubtotal = $quantity * $unitPrice;

            // Calculate line tax
            $lineTax = 0;
            if ($taxId) {
                $tax = Tax::find($taxId);
                if ($tax) {
                    $lineTax = $lineSubtotal * ($tax->rate / 100);
                }
            }

            // Calculate line total
            $lineTotal = $lineSubtotal + $lineTax;

            $totalAmount += $lineTotal;
            $totalTax += $lineTax;
        }

        // Set the totals
        $set('total_amount', $totalAmount);
        $set('total_tax', $totalTax);
    }
}
