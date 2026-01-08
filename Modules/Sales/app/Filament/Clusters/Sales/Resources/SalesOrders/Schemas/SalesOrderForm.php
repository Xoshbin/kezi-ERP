<?php

namespace Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Schemas;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Enums\Accounting\TaxType;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Enums\Incoterm;
use Modules\Foundation\Filament\Forms\Components\MoneyInput;
use Modules\Product\Models\Product;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Models\SalesOrder;

class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('sales::sales_orders.sections.basic_info'))
                    ->schema([
                        Hidden::make('company_id')
                            ->default(fn () => Auth::user()?->company_id),

                        Hidden::make('created_by_user_id')
                            ->default(fn () => Auth::id()),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('so_number')
                                    ->label(__('sales::sales_orders.fields.so_number'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder(__('sales::sales_orders.help.so_number')),

                                Select::make('status')
                                    ->label(__('sales::sales_orders.fields.status'))
                                    ->options(function (?string $operation, ?SalesOrder $record) {
                                        // In create mode, show all statuses
                                        if ($operation === 'create') {
                                            return SalesOrderStatus::class;
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

                                        return SalesOrderStatus::class;
                                    })
                                    ->default(SalesOrderStatus::Draft)
                                    ->disabled(function (?string $operation, ?SalesOrder $record) {
                                        // Always editable in create mode
                                        if ($operation === 'create') {
                                            return false;
                                        }

                                        // In edit mode, allow editing if status allows flexibility
                                        if ($operation === 'edit' && $record) {
                                            // Allow editing for active statuses that might need manual adjustment
                                            return ! in_array($record->status, [
                                                SalesOrderStatus::Draft,
                                                SalesOrderStatus::Sent,
                                                SalesOrderStatus::Confirmed,
                                                SalesOrderStatus::ToDeliver,
                                                SalesOrderStatus::PartiallyDelivered,
                                                SalesOrderStatus::FullyDelivered,
                                                SalesOrderStatus::ToInvoice,
                                                SalesOrderStatus::PartiallyInvoiced,
                                            ]);
                                        }

                                        return true;
                                    })
                                    ->helperText(function (?string $operation, ?SalesOrder $record) {
                                        if ($operation === 'edit' && $record) {
                                            $messages = [];

                                            // Invoice creation status message
                                            if ($record->canCreateInvoice()) {
                                                $messages[] = __('sales::sales_orders.help.status_can_create_invoice');
                                            } else {
                                                $messages[] = __('sales::sales_orders.help.status_cannot_create_invoice');
                                            }

                                            // Delivery status message
                                            if ($record->canDeliverGoods()) {
                                                $messages[] = __('sales::sales_orders.help.status_can_deliver');
                                            } else {
                                                $messages[] = __('sales::sales_orders.help.status_cannot_deliver');
                                            }

                                            return implode(' ', $messages);
                                        }

                                        return null;
                                    })
                                    ->native(false)
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('so_date')
                                    ->label(__('sales::sales_orders.fields.order_date'))
                                    ->required()
                                    ->default(now())
                                    ->native(false),

                                TextInput::make('reference')
                                    ->label(__('sales::sales_orders.fields.reference'))
                                    ->maxLength(255)
                                    ->helperText(__('sales::sales_orders.help.reference')),
                            ]),
                    ]),

                Section::make(__('sales::sales_orders.sections.customer_details'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('customer_id')
                                    ->label(__('sales::sales_orders.fields.customer'))
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionAction(
                                        fn (Action $action) => $action
                                            ->modalHeading(__('sales::partner.create_customer'))
                                            ->modalSubmitActionLabel(__('sales::partner.create'))
                                            ->modalWidth('lg')
                                    ),

                                Select::make('currency_id')
                                    ->label(__('sales::sales_orders.fields.currency'))
                                    ->relationship('currency', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(fn () => Filament::getTenant()?->currency_id)
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

                                        $currency = \Modules\Foundation\Models\Currency::find($currencyId);
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
                                                    // For Sales Orders, we use unit_price
                                                    if ($product && $product->unit_price) {
                                                        $basePrice = $product->unit_price instanceof \Brick\Money\Money
                                                            ? $product->unit_price->getAmount()->toBigDecimal()
                                                            : \Brick\Math\BigDecimal::of($product->unit_price);

                                                        if ($newRate == 1.0) {
                                                            // Reverting to base currency
                                                            $lines[$uuid]['unit_price'] = (string) $basePrice;
                                                        } else {
                                                            // Converting to foreign currency
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
                                    ->label(__('sales::sales_orders.fields.exchange_rate'))
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
                                            $currency = \Modules\Foundation\Models\Currency::find($currencyId);
                                            if ($currency) {
                                                $service = app(\Modules\Foundation\Services\CurrencyConverterService::class);
                                                $latestRate = $service->getExchangeRate($currency, now(), $company) ?? $service->getLatestExchangeRate($currency, $company);

                                                if ($latestRate) {
                                                    return __('sales::sales_orders.help.exchange_rate').' '.__('sales::sales_orders.help.current_rate', ['rate' => $latestRate]);
                                                }
                                            }
                                        }

                                        return __('sales::sales_orders.help.exchange_rate');
                                    }),

                                Select::make('incoterm')
                                    ->label(__('sales::sales_orders.fields.incoterm'))
                                    ->options(Incoterm::class)
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

                Section::make(__('sales::sales_orders.sections.delivery_info'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('expected_delivery_date')
                                    ->label(__('sales::sales_orders.fields.expected_delivery_date'))
                                    ->native(false),

                                Select::make('delivery_location_id')
                                    ->label(__('sales::sales_orders.fields.delivery_location'))
                                    ->relationship('deliveryLocation', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

                Section::make(__('sales::sales_orders.sections.line_items'))
                    ->description(__('sales::sales_orders.sections.line_items_description'))
                    ->schema([
                        Repeater::make('lines')
                            ->label(__('sales::sales_orders.fields.lines'))
                            ->table([
                                Repeater\TableColumn::make(__('sales::sales_orders.fields.product'))->width('20%'),
                                Repeater\TableColumn::make(__('sales::sales_orders.fields.description'))->width('20%'),
                                Repeater\TableColumn::make(__('sales::sales_orders.fields.quantity'))->width('10%'),
                                Repeater\TableColumn::make(__('sales::sales_orders.fields.unit_price'))->width('15%'),
                                Repeater\TableColumn::make(__('sales::sales_orders.fields.tax'))->width('15%'),
                                Repeater\TableColumn::make(__('sales::sales_orders.fields.expected_delivery_date'))->width('15%'),
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
                                    ->label(__('sales::sales_orders.fields.product'))
                                    ->options(Product::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('description', $product->name);

                                                // Handle Price Conversion
                                                $exchangeRate = (float) $get('../../exchange_rate_at_creation') ?: 1.0;

                                                // Prepare base price from Product unit_price
                                                $basePrice = \Brick\Math\BigDecimal::zero();
                                                if ($product->unit_price) {
                                                    $basePrice = $product->unit_price instanceof \Brick\Money\Money
                                                        ? $product->unit_price->getAmount()->toBigDecimal()
                                                        : \Brick\Math\BigDecimal::of($product->unit_price);
                                                }

                                                if ($exchangeRate > 0 && $exchangeRate != 1.0) {
                                                    $converted = $basePrice->dividedBy($exchangeRate, 6, \Brick\Math\RoundingMode::HALF_UP);
                                                    $set('unit_price', (string) $converted);
                                                } else {
                                                    $set('unit_price', (string) $basePrice);
                                                }
                                            }
                                        }
                                        static::updateTotals($set, $get);
                                    })
                                    ->createOptionAction(
                                        fn (Action $action) => $action
                                            ->modalHeading(__('product::product.create'))
                                            ->modalSubmitActionLabel(__('product::product.create'))
                                            ->modalWidth('lg')
                                    )
                                    ->columnSpan(3),

                                TextInput::make('description')
                                    ->label(__('sales::sales_orders.fields.description'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(4),

                                TextInput::make('quantity')
                                    ->label(__('sales::sales_orders.fields.quantity'))
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->extraInputAttributes(['onclick' => 'this.select()'])
                                    ->columnSpan(2),

                                MoneyInput::make('unit_price')
                                    ->label(__('sales::sales_orders.fields.unit_price'))
                                    ->currencyField('../../currency_id')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(3),

                                Select::make('tax_id')
                                    ->label(__('sales::sales_orders.fields.tax'))
                                    ->options(Tax::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->options(function () {
                                        return Tax::where('company_id', Filament::getTenant()?->id)
                                            ->where('type', TaxType::Sales)
                                            ->pluck('name', 'id');
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(3),

                                DatePicker::make('expected_delivery_date')
                                    ->label(__('sales::sales_orders.fields.line_expected_delivery_date'))
                                    ->native(false)
                                    ->columnSpan(3),
                            ])
                            ->columns(18),
                    ])->columnSpanFull(),

                Section::make(__('sales::sales_orders.sections.totals'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                MoneyInput::make('total_tax')
                                    ->label(__('sales::sales_orders.fields.total_tax'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),

                                MoneyInput::make('total_amount')
                                    ->label(__('sales::sales_orders.fields.total_amount'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make(__('sales::sales_orders.sections.notes'))
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('sales::sales_orders.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('terms_and_conditions')
                            ->label(__('sales::sales_orders.fields.terms_and_conditions'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

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
        $currency = \Modules\Foundation\Models\Currency::find($currencyId);
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
