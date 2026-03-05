<?php

namespace Kezi\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Schemas;

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
use Kezi\Accounting\Filament\Forms\Components\TaxSelectField;
use Kezi\Foundation\Enums\Incoterm;
use Kezi\Foundation\Filament\Forms\Components\ExchangeRateInput;
use Kezi\Foundation\Filament\Forms\Components\MoneyInput;
use Kezi\Foundation\Filament\Forms\Components\PartnerSelectField;
use Kezi\Foundation\Filament\Helpers\DocumentTotalsHelper;
use Kezi\Product\Filament\Forms\Components\ProductSelectField;
use Kezi\Product\Models\Product;
use Kezi\Sales\Enums\Sales\SalesOrderStatus;
use Kezi\Sales\Models\SalesOrder;

class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('sales::sales_orders.sections.basic_info'))
                    ->schema([
                        Hidden::make('company_id')
                            ->default(fn () => Filament::getTenant()?->getKey()),

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
                                PartnerSelectField::make('customer_id')
                                    ->label(__('sales::sales_orders.fields.customer'))
                                    ->required(),

                                \Kezi\Foundation\Filament\Forms\Components\CurrencySelectField::make('currency_id')
                                    ->label(__('sales::sales_orders.fields.currency'))
                                    ->required()
                                    ->exchangeRateFieldName('exchange_rate_at_creation'),

                                ExchangeRateInput::make('exchange_rate_at_creation'),

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
                            ->schema([
                                ProductSelectField::make('product_id')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('description', $product->description ?: $product->name);

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
                                    })
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
                                    ->extraInputAttributes(['onclick' => 'this.select()'])
                                    ->columnSpan(2),

                                MoneyInput::make('unit_price')
                                    ->label(__('sales::sales_orders.fields.unit_price'))
                                    ->currencyField('../../currency_id')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->columnSpan(3),
                                TaxSelectField::make('tax_id')
                                    ->label(__('sales::sales_orders.fields.tax'))
                                    ->taxFilter([\Kezi\Accounting\Enums\Accounting\TaxType::Sales, \Kezi\Accounting\Enums\Accounting\TaxType::Both])
                                    ->createOptionDefaultType(\Kezi\Accounting\Enums\Accounting\TaxType::Sales)
                                    ->live()
                                    ->columnSpan(3),

                                DatePicker::make('expected_delivery_date')
                                    ->label(__('sales::sales_orders.fields.line_expected_delivery_date'))
                                    ->native(false)
                                    ->columnSpan(3),
                            ])
                            ->columns(18),
                    ])->columnSpanFull(),

                DocumentTotalsHelper::make(
                    linesKey: 'lines',
                    translationPrefix: 'sales::sales_orders.fields',
                    totalsLabel: __('sales::sales_orders.sections.totals'),
                    taxLabel: __('sales::sales_orders.fields.total_tax'),
                    totalLabel: __('sales::sales_orders.fields.total_amount'),
                    companyCurrencyTotalLabel: __('accounting::bill.total_amount_company_currency'),
                    exchangeRateKey: 'exchange_rate_at_creation'
                )->collapsible()->collapsed(false),

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
}
