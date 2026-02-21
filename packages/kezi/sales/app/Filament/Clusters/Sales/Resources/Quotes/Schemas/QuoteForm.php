<?php

namespace Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Kezi\Accounting\Enums\Accounting\TaxType;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Filament\Forms\Components\ExchangeRateInput;
use Kezi\Foundation\Filament\Forms\Components\MoneyInput;
use Kezi\Foundation\Models\Currency;
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
                                    ->columnSpan(2),

                                MoneyInput::make('unit_price')
                                    ->label(__('sales::quote.fields.unit_price'))
                                    ->currencyField('../../currency_id')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->columnSpan(3),

                                TextInput::make('discount_percentage')
                                    ->label(__('sales::quote.fields.discount_percentage'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->live(onBlur: true)
                                    ->columnSpan(2),

                                Select::make('tax_id')
                                    ->label(__('sales::quote.fields.tax'))
                                    ->options(function () {
                                        return Tax::where('company_id', Filament::getTenant()?->getKey())
                                            ->where('type', TaxType::Sales->value)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->columnSpan(3),
                            ])
                            ->columns(18),
                    ])->columnSpanFull(),

                Section::make(__('sales::quote.sections.totals'))
                    ->schema([
                        \Filament\Schemas\Components\Fieldset::make(__('sales::quote.fields.document_currency'))
                            ->schema([
                                Placeholder::make('subtotal_display')
                                    ->label(__('sales::quote.fields.subtotal'))
                                    ->content(function (Get $get) {
                                        return static::calculateTotalDisplay($get, 'subtotal');
                                    }),

                                Placeholder::make('discount_total_display')
                                    ->label(__('sales::quote.fields.discount_total'))
                                    ->content(function (Get $get) {
                                        return static::calculateTotalDisplay($get, 'discount');
                                    }),

                                Placeholder::make('tax_total_display')
                                    ->label(__('sales::quote.fields.tax_total'))
                                    ->content(function (Get $get) {
                                        return static::calculateTotalDisplay($get, 'tax');
                                    }),

                                Placeholder::make('total_display')
                                    ->label(__('sales::quote.fields.total'))
                                    ->content(function (Get $get) {
                                        return static::calculateTotalDisplay($get, 'total');
                                    }),
                            ])
                            ->columns(4),

                        \Filament\Schemas\Components\Fieldset::make(__('sales::quote.fields.company_currency'))
                            ->schema([
                                Placeholder::make('subtotal_company_currency_display')
                                    ->label(__('sales::quote.fields.subtotal'))
                                    ->content(function (Get $get) {
                                        return static::calculateTotalDisplay($get, 'subtotal', true);
                                    }),

                                Placeholder::make('discount_total_company_currency_display')
                                    ->label(__('sales::quote.fields.discount_total'))
                                    ->content(function (Get $get) {
                                        return static::calculateTotalDisplay($get, 'discount', true);
                                    }),

                                Placeholder::make('tax_total_company_currency_display')
                                    ->label(__('sales::quote.fields.tax_total'))
                                    ->content(function (Get $get) {
                                        return static::calculateTotalDisplay($get, 'tax', true);
                                    }),

                                Placeholder::make('total_company_currency_display')
                                    ->label(__('sales::quote.fields.total'))
                                    ->content(function (Get $get) {
                                        return static::calculateTotalDisplay($get, 'total', true);
                                    }),
                            ])
                            ->columns(4)
                            ->visible(function (Get $get) {
                                $company = Filament::getTenant();

                                return $company && $get('currency_id') && $get('currency_id') != $company->currency_id;
                            }),
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

    public static function calculateTotalDisplay(Get $get, string $type, bool $inCompanyCurrency = false): string
    {
        /** @var array<int|string, array<string, mixed>> $linesData */
        $linesData = $get('lines') ?? [];
        $lines = $linesData;
        $currencyId = $get('currency_id');
        /** @var \Kezi\Foundation\Models\Currency|null $currency */
        $currency = $currencyId ? Currency::where('id', $currencyId)->first() : null;

        if (! $currency || count($lines) === 0) {
            return '-';
        }

        $subtotal = \Brick\Math\BigDecimal::zero();
        $discountTotal = \Brick\Math\BigDecimal::zero();
        $taxTotal = \Brick\Math\BigDecimal::zero();

        foreach ($lines as $line) {
            $quantity = \Brick\Math\BigDecimal::of(filled($line['quantity'] ?? null) ? $line['quantity'] : 0);
            $unitPrice = \Brick\Math\BigDecimal::of(filled($line['unit_price'] ?? null) ? $line['unit_price'] : 0);
            $discountPct = \Brick\Math\BigDecimal::of(filled($line['discount_percentage'] ?? null) ? $line['discount_percentage'] : 0);
            $taxId = $line['tax_id'] ?? null;

            if ($quantity->isZero() || $unitPrice->isZero()) {
                continue;
            }

            $lineGross = $quantity->multipliedBy($unitPrice);
            $lineDiscount = $lineGross->multipliedBy($discountPct)->dividedBy(100, 10, \Brick\Math\RoundingMode::HALF_UP);
            $lineSubtotal = $lineGross->minus($lineDiscount);

            $lineTax = \Brick\Math\BigDecimal::zero();
            if ($taxId) {
                $tax = Tax::find($taxId);
                if ($tax instanceof Tax) {
                    $lineTax = $lineSubtotal->multipliedBy($tax->rate)->dividedBy(100, 10, \Brick\Math\RoundingMode::HALF_UP);
                }
            }

            $subtotal = $subtotal->plus($lineSubtotal);
            $discountTotal = $discountTotal->plus($lineDiscount);
            $taxTotal = $taxTotal->plus($lineTax);
        }

        $amount = match ($type) {
            'subtotal' => (float) (string) $subtotal,
            'discount' => (float) (string) $discountTotal,
            'tax' => (float) (string) $taxTotal,
            'total' => (float) (string) $subtotal->plus($taxTotal),
            default => 0,
        };

        if ($inCompanyCurrency) {
            $exchangeRate = (float) ($get('exchange_rate') ?? 1.0);
            $company = Filament::getTenant();
            $companyCurrency = $company ? Currency::find($company->currency_id) : null;

            if (! $companyCurrency) {
                return '-';
            }

            $totalInLocal = $amount * $exchangeRate;

            return $companyCurrency->symbol.' '.number_format($totalInLocal, $companyCurrency->decimal_places);
        }

        return $currency->symbol.' '.number_format($amount, $currency->decimal_places);
    }
}
