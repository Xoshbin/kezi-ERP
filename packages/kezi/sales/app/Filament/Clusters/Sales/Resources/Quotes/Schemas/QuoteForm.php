<?php

namespace Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Schemas;

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
use Kezi\Accounting\Enums\Accounting\TaxType;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Filament\Forms\Components\ExchangeRateInput;
use Kezi\Foundation\Filament\Forms\Components\MoneyInput;
use Kezi\Product\Models\Product;
use Kezi\Sales\Enums\Sales\QuoteStatus;

class QuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('sales::quote.sections.basic_info'))
                    ->schema([
                        Hidden::make('company_id')
                            ->default(fn () => Filament::getTenant()?->id),

                        Hidden::make('created_by_user_id')
                            ->default(fn () => Auth::id()),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('quote_number')
                                    ->label(__('sales::quote.fields.quote_number'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder(__('sales::quote.help.quote_number')),

                                Select::make('status')
                                    ->label(__('sales::quote.fields.status'))
                                    ->options(QuoteStatus::class)
                                    ->default(QuoteStatus::Draft)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->native(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('quote_date')
                                    ->label(__('sales::quote.fields.quote_date'))
                                    ->required()
                                    ->default(now())
                                    ->native(false),

                                DatePicker::make('valid_until')
                                    ->label(__('sales::quote.fields.valid_until'))
                                    ->required()
                                    ->default(now()->addDays(30))
                                    ->native(false)
                                    ->helperText(__('sales::quote.help.valid_until')),
                            ]),
                    ]),

                Section::make(__('sales::quote.sections.basic_info'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('partner_id')
                                    ->label(__('sales::quote.fields.partner'))
                                    ->relationship('partner', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('currency_id')
                                    ->label(__('sales::quote.fields.currency'))
                                    ->relationship('currency', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(fn () => Filament::getTenant()?->currency_id)
                                    ->live()
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('exchange_rate', 1);
                                    }),

                                ExchangeRateInput::make('exchange_rate'),
                            ]),
                    ]),

                Section::make(__('sales::quote.sections.line_items'))
                    ->schema([
                        Repeater::make('lines')
                            ->label(__('sales::quote.fields.lines'))
                            ->table([
                                Repeater\TableColumn::make(__('sales::quote.fields.product'))->width('15%'),
                                Repeater\TableColumn::make(__('sales::quote.fields.description'))->width('25%'),
                                Repeater\TableColumn::make(__('sales::quote.fields.quantity'))->width('10%'),
                                Repeater\TableColumn::make(__('sales::quote.fields.unit_price'))->width('15%'),
                                Repeater\TableColumn::make(__('sales::quote.fields.discount_percentage'))->width('10%'),
                                Repeater\TableColumn::make(__('sales::quote.fields.tax'))->width('15%'),
                            ])
                            ->live()
                            ->reorderable(true)
                            ->minItems(1)
                            ->afterStateHydrated(function (callable $set, callable $get) {
                                static::updateTotals($set, $get);
                            })
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                static::updateTotals($set, $get);
                            })
                            ->schema([
                                Select::make('product_id')
                                    ->label(__('sales::quote.fields.product'))
                                    ->options(Product::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('description', $product->name);
                                                $basePrice = $product->unit_price instanceof \Brick\Money\Money
                                                    ? $product->unit_price->getAmount()->toBigDecimal()
                                                    : \Brick\Math\BigDecimal::of($product->unit_price ?? 0);
                                                $set('unit_price', (string) $basePrice);
                                            }
                                        }
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(3),

                                TextInput::make('description')
                                    ->label(__('sales::quote.fields.description'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(5),

                                TextInput::make('quantity')
                                    ->label(__('sales::quote.fields.quantity'))
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(2),

                                MoneyInput::make('unit_price')
                                    ->label(__('sales::quote.fields.unit_price'))
                                    ->currencyField('../../currency_id')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(3),

                                TextInput::make('discount_percentage')
                                    ->label(__('sales::quote.fields.discount_percentage'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(2),

                                Select::make('tax_id')
                                    ->label(__('sales::quote.fields.tax'))
                                    ->options(function () {
                                        return Tax::where('company_id', Filament::getTenant()?->id)
                                            ->where('type', TaxType::Sales)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(3),
                            ])
                            ->columns(18),
                    ])->columnSpanFull(),

                Section::make(__('sales::quote.sections.totals'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                MoneyInput::make('subtotal')
                                    ->label(__('sales::quote.fields.subtotal'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),

                                MoneyInput::make('discount_total')
                                    ->label(__('sales::quote.fields.discount_total'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),

                                MoneyInput::make('tax_total')
                                    ->label(__('sales::quote.fields.tax_total'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),

                                MoneyInput::make('total')
                                    ->label(__('sales::quote.fields.total'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make(__('sales::quote.sections.additional_info'))
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('sales::quote.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('terms_and_conditions')
                            ->label(__('sales::quote.fields.terms_and_conditions'))
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
            $set('subtotal', 0);
            $set('discount_total', 0);
            $set('tax_total', 0);
            $set('total', 0);

            return;
        }

        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $discountPct = (float) ($line['discount_percentage'] ?? 0);
            $taxId = $line['tax_id'] ?? null;

            if ($quantity <= 0 || $unitPrice <= 0) {
                continue;
            }

            $lineGross = $quantity * $unitPrice;
            $lineDiscount = $lineGross * ($discountPct / 100);
            $lineSubtotal = $lineGross - $lineDiscount;

            $lineTax = 0;
            if ($taxId) {
                $tax = Tax::find($taxId);
                if ($tax) {
                    $lineTax = $lineSubtotal * ($tax->rate / 100);
                }
            }

            $subtotal += $lineSubtotal;
            $discountTotal += $lineDiscount;
            $taxTotal += $lineTax;
        }

        $set('subtotal', $subtotal);
        $set('discount_total', $discountTotal);
        $set('tax_total', $taxTotal);
        $set('total', $subtotal + $taxTotal);
    }
}
