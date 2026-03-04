<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Schemas;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Enums\Partners\PartnerType;
use Kezi\Foundation\Filament\Forms\Components\ExchangeRateInput;
use Kezi\Foundation\Filament\Forms\Components\MoneyInput;
use Kezi\Foundation\Filament\Helpers\DocumentTotalsHelper;
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
                                    ->default(fn () => Filament::getTenant()?->getAttribute('currency_id'))
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        $currencyId = $state;
                                        if (! $currencyId) {
                                            $set('exchange_rate', 1);

                                            return;
                                        }

                                        /** @var \App\Models\Company|null $company */
                                        $company = Filament::getTenant();

                                        /** @var \Kezi\Foundation\Models\Currency|null $currency */
                                        $currency = Currency::find($currencyId);
                                        /** @var \Kezi\Foundation\Models\Currency|null $baseCurrency */
                                        $baseCurrency = $company?->currency;
                                        $newRate = 1.0;

                                        if ($company && $currency && $baseCurrency) {
                                            if ($currency->id === $baseCurrency->id) {
                                                $set('exchange_rate', 1);
                                                $newRate = 1.0;
                                            } else {
                                                /** @var \Kezi\Foundation\Services\CurrencyConverterService $service */
                                                $service = app(\Kezi\Foundation\Services\CurrencyConverterService::class);
                                                $rate = $service->getExchangeRate($currency, now(), $company) ?? $service->getLatestExchangeRate($currency, $company);
                                                $newRate = $rate ?? 1.0;
                                                $set('exchange_rate', $newRate);
                                            }
                                        }

                                        // Recalculate prices for existing lines
                                        /** @var array<string, array<string, mixed>> $lines */
                                        $lines = $get('lines') ?? [];
                                        if (! empty($lines)) {
                                            /** @var \Illuminate\Support\Collection<int, int|string> $productIds */
                                            $productIds = collect($lines)
                                                ->pluck('product_id')
                                                ->filter()
                                                ->unique()
                                                ->values();

                                            $products = Product::findMany($productIds->toArray())->keyBy('id');

                                            foreach ($lines as $uuid => $line) {
                                                if (isset($line['product_id'])) {
                                                    $product = $products->get($line['product_id']);

                                                    if ($product instanceof Product && $product->average_cost) {
                                                        // Get the underlying decimal amount from the Money object or value
                                                        $basePrice = $product->average_cost->getAmount()->toBigDecimal();

                                                        if ($newRate == 1.0) {
                                                            // Reverting to base currency: use original base price
                                                            $lines[$uuid]['unit_price'] = (string) $basePrice;
                                                        } else {
                                                            // Converting to foreign currency: Base / Rate
                                                            if ($newRate > 0) {
                                                                $converted = BigDecimal::of($basePrice)->dividedBy($newRate, 6, RoundingMode::HALF_UP);
                                                                $lines[$uuid]['unit_price'] = (string) $converted;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            $set('lines', $lines);
                                        }
                                    }),

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
                                \Kezi\Product\Filament\Forms\Components\ProductSelectField::make('product_id')
                                    ->required()
                                    ->live()
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
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('unit')
                                    ->label(__('purchase::request_for_quotation.lines.unit'))
                                    ->columnSpan(2),

                                MoneyInput::make('unit_price')
                                    ->label(__('purchase::request_for_quotation.lines.unit_price'))
                                    ->currencyField('../../currency_id')
                                    ->live(onBlur: true)
                                    ->columnSpan(3),

                                Forms\Components\Select::make('tax_id')
                                    ->label(__('purchase::request_for_quotation.lines.tax'))
                                    ->options(fn () => Tax::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->columnSpan(3),
                            ])
                            ->columns(17)
                            ->defaultItems(1),
                    ])->columnSpanFull(),

                DocumentTotalsHelper::make(
                    linesKey: 'lines',
                    translationPrefix: 'purchase::request_for_quotation.fields',
                    totalsLabel: __('purchase::request_for_quotation.sections.totals'),
                    subtotalLabel: __('purchase::request_for_quotation.fields.subtotal'),
                    taxLabel: __('purchase::request_for_quotation.fields.tax_total'),
                    totalLabel: __('purchase::request_for_quotation.fields.total'),
                    companyCurrencyTotalLabel: __('accounting::bill.total_amount_company_currency'),
                    exchangeRateKey: 'exchange_rate'
                )->collapsible()->collapsed(false),

                Section::make(__('purchase::request_for_quotation.sections.notes'))
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('purchase::request_for_quotation.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
