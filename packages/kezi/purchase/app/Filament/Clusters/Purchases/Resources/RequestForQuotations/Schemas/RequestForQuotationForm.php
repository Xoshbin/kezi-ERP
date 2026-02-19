<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Schemas;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Enums\Partners\PartnerType;
use Kezi\Foundation\Filament\Forms\Components\ExchangeRateInput;
use Kezi\Foundation\Filament\Forms\Components\MoneyInput;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus;

class RequestForQuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('purchase::request_for_quotation.sections.basic_info'))
                    ->schema([
                        Forms\Components\Hidden::make('company_id')
                            ->default(fn () => Filament::getTenant()?->getAttribute('id')),

                        Forms\Components\Hidden::make('created_by_user_id')
                            ->default(fn () => Auth::id()),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('rfq_number')
                                    ->label(__('purchase::request_for_quotation.fields.rfq_number'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder(__('purchase::purchase_orders.help.po_number')),

                                Forms\Components\Select::make('status')
                                    ->label(__('purchase::request_for_quotation.fields.status'))
                                    ->options(RequestForQuotationStatus::class)
                                    ->default(RequestForQuotationStatus::Draft)
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('rfq_date')
                                    ->label(__('purchase::request_for_quotation.fields.rfq_date'))
                                    ->default(now())
                                    ->required(),

                                Forms\Components\DatePicker::make('valid_until')
                                    ->label(__('purchase::request_for_quotation.fields.valid_until')),
                            ]),
                    ]),

                Section::make(__('purchase::request_for_quotation.sections.vendor_info'))
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('vendor_id')
                                    ->label(__('purchase::request_for_quotation.fields.vendor'))
                                    ->options(fn () => Partner::query()->whereIn('type', [PartnerType::Vendor, PartnerType::Both])->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('currency_id')
                                    ->label(__('purchase::request_for_quotation.fields.currency'))
                                    ->options(fn () => Currency::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => Currency::where('code', 'USD')->first()?->id)
                                    ->required()
                                    ->live(),

                                ExchangeRateInput::make('exchange_rate')
                                    ->label(__('purchase::request_for_quotation.fields.exchange_rate')),
                            ]),
                    ]),

                Section::make(__('purchase::request_for_quotation.sections.line_items'))
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->table([
                                Forms\Components\Repeater\TableColumn::make(__('purchase::request_for_quotation.lines.product'))->width('25%'),
                                Forms\Components\Repeater\TableColumn::make(__('purchase::request_for_quotation.lines.description'))->width('25%'),
                                Forms\Components\Repeater\TableColumn::make(__('purchase::request_for_quotation.lines.quantity'))->width('10%'),
                                Forms\Components\Repeater\TableColumn::make(__('purchase::request_for_quotation.lines.unit'))->width('10%'),
                                Forms\Components\Repeater\TableColumn::make(__('purchase::request_for_quotation.lines.unit_price'))->width('15%'),
                                Forms\Components\Repeater\TableColumn::make(__('purchase::request_for_quotation.lines.tax'))->width('15%'),
                            ])
                            ->live()
                            ->reorderable(true)
                            ->minItems(1)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label(__('purchase::request_for_quotation.lines.product'))
                                    ->options(fn () => Product::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product instanceof Product) {
                                                $set('description', $product->description ?: $product->name);
                                                $set('unit_price', $product->average_cost?->getAmount()->toFloat() ?? 0);
                                            }
                                        }
                                    })
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('description')
                                    ->label(__('purchase::request_for_quotation.lines.description'))
                                    ->required()
                                    ->columnSpan(4),

                                Forms\Components\TextInput::make('quantity')
                                    ->label(__('purchase::request_for_quotation.lines.quantity'))
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('unit')
                                    ->label(__('purchase::request_for_quotation.lines.unit'))
                                    ->columnSpan(2),

                                MoneyInput::make('unit_price')
                                    ->label(__('purchase::request_for_quotation.lines.unit_price'))
                                    ->currencyField('../../currency_id')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(3),

                                Forms\Components\Select::make('tax_id')
                                    ->label(__('purchase::request_for_quotation.lines.tax'))
                                    ->options(fn () => Tax::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(3),
                            ])
                            ->columns(17)
                            ->defaultItems(1)
                            ->afterStateHydrated(function (callable $set, callable $get) {
                                static::updateTotals($set, $get);
                            })
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                static::updateTotals($set, $get);
                            }),
                    ])->columnSpanFull(),

                Section::make(__('purchase::request_for_quotation.sections.totals'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                MoneyInput::make('subtotal')
                                    ->label(__('purchase::request_for_quotation.fields.subtotal'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),

                                MoneyInput::make('tax_total')
                                    ->label(__('purchase::request_for_quotation.fields.tax_total'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),

                                MoneyInput::make('total')
                                    ->label(__('purchase::request_for_quotation.fields.total'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make(__('purchase::request_for_quotation.sections.notes'))
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('purchase::request_for_quotation.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Update the RFQ totals based on line items
     */
    public static function updateTotals(callable $set, callable $get): void
    {
        $lines = $get('lines') ?? [];
        $currencyId = $get('currency_id');

        if (! $currencyId || empty($lines)) {
            $set('total', 0);
            $set('subtotal', 0);
            $set('tax_total', 0);

            return;
        }

        // Get currency for calculations
        $currency = Currency::find($currencyId);
        if (! $currency) {
            return;
        }

        $subtotal = \Brick\Math\BigDecimal::zero();
        $totalTax = \Brick\Math\BigDecimal::zero();

        foreach ($lines as $line) {
            $quantity = \Brick\Math\BigDecimal::of(filled($line['quantity'] ?? null) ? $line['quantity'] : 0);
            $unitPrice = \Brick\Math\BigDecimal::of(filled($line['unit_price'] ?? null) ? $line['unit_price'] : 0);
            $taxId = $line['tax_id'] ?? null;

            if ($quantity->isZero() || $unitPrice->isZero()) {
                continue;
            }

            // Calculate line subtotal
            $lineSubtotal = $quantity->multipliedBy($unitPrice);
            $subtotal = $subtotal->plus($lineSubtotal);

            // Calculate line tax
            if ($taxId) {
                $tax = Tax::find($taxId);
                if ($tax instanceof Tax) {
                    $lineTax = $lineSubtotal->multipliedBy($tax->rate)->dividedBy(100, 10, \Brick\Math\RoundingMode::HALF_UP);
                    $totalTax = $totalTax->plus($lineTax);
                }
            }
        }

        // Set the totals
        $set('subtotal', (string) $subtotal);
        $set('tax_total', (string) $totalTax);
        $set('total', (string) $subtotal->plus($totalTax));
    }
}
