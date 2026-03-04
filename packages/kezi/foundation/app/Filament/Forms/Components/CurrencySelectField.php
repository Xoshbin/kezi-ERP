<?php

namespace Kezi\Foundation\Filament\Forms\Components;

use App\Models\Company;
use Brick\Money\Money;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Kezi\Product\Models\Product;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class CurrencySelectField extends TranslatableSelect
{
    protected ?string $exchangeRateFieldName = 'exchange_rate_at_creation';

    protected ?string $linesRepeaterName = 'lines';

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelClass = Currency::class;
        $this->relationshipTitleAttribute = 'name';
        $this->configureForModel();

        $this->label(__('accounting::bill.currency')); // default label, can be overridden
        $this->searchable();
        $this->preload();

        $this->default(function (): ?int {
            $tenant = Filament::getTenant();

            return $tenant instanceof Company ? $tenant->currency_id : (Auth::user()?->company->currency_id ?? null);
        });

        $this->createOptionForm([
            TextInput::make('code')
                ->label(__('accounting::currency.code'))
                ->required()
                ->maxLength(255),
            TextInput::make('name')
                ->label(__('accounting::currency.name'))
                ->required()
                ->maxLength(255),
            TextInput::make('symbol')
                ->label(__('accounting::currency.symbol'))
                ->required()
                ->maxLength(5),
            TextInput::make('exchange_rate')
                ->label(__('accounting::currency.exchange_rate'))
                ->required()
                ->numeric()
                ->default(1),
            Toggle::make('is_active')
                ->label(__('accounting::currency.is_active'))
                ->required()
                ->default(true),
        ]);

        $this->createOptionModalHeading(__('accounting::common.modal_title_create_currency'));

        $this->createOptionAction(function (Action $action) {
            return $action->modalWidth('lg');
        });

        $this->createOptionUsing(function (array $data): int {
            $currency = Currency::create($data);

            return $currency->getKey();
        });

        $this->live();

        $this->afterStateUpdated(function (callable $set, callable $get, $state, $component) {
            $currencyId = $state;

            $exchangeRateFieldName = $component->getExchangeRateFieldName();
            $linesRepeaterName = $component->getLinesRepeaterName();

            if (! $currencyId) {
                if ($exchangeRateFieldName) {
                    $set($exchangeRateFieldName, 1);
                }

                return;
            }

            $company = Filament::getTenant();
            if (! $company) {
                $company = Auth::user()?->company;
            }

            if (! $company instanceof Company) {
                return;
            }

            $currency = Currency::find($currencyId);
            if ($currency instanceof Collection) {
                $currency = $currency->first();
            }

            $baseCurrency = $company->currency ?? null;
            $newRate = 1.0;

            if ($currency instanceof Currency && $baseCurrency instanceof Currency) {
                if ($currency->id === $baseCurrency->id) {
                    if ($exchangeRateFieldName) {
                        $set($exchangeRateFieldName, 1);
                    }
                    $newRate = 1.0;
                } else {
                    $service = app(\Kezi\Foundation\Services\CurrencyConverterService::class);
                    $rate = $service->getExchangeRate($currency, now(), $company) ?? $service->getLatestExchangeRate($currency, $company);
                    $newRate = $rate ?? 1.0;
                    if ($exchangeRateFieldName) {
                        $set($exchangeRateFieldName, $newRate);
                    }
                }
            } elseif ($currency instanceof Currency && $currency->id !== $company->currency_id) {
                // Fallback to CurrencyRate logic if service is not sufficient
                $latestRate = CurrencyRate::getLatestRate($currency->id, $company->id);
                if ($latestRate) {
                    $newRate = $latestRate;
                    if ($exchangeRateFieldName) {
                        $set($exchangeRateFieldName, $newRate);
                    }
                }
            }

            // Recalculate prices for existing lines
            if ($linesRepeaterName) {
                $lines = $get($linesRepeaterName) ?? [];
                if (! empty($lines)) {
                    foreach ($lines as $uuid => $line) {
                        if (isset($line['product_id'])) {
                            $product = Product::find($line['product_id']);
                            if ($product instanceof Product && $product->unit_price) {
                                // Get the underlying decimal amount from the Money object or value
                                $basePrice = $product->unit_price->getAmount()->toBigDecimal();

                                if ($newRate == 1.0) {
                                    // Reverting to base currency: use original base price
                                    $lines[$uuid]['unit_price'] = (string) $basePrice;
                                } else {
                                    // Converting to foreign currency: Base / Rate
                                    if ($newRate > 0) {
                                        $converted = \Brick\Math\BigDecimal::of($basePrice)->dividedBy($newRate, 6, \Brick\Math\RoundingMode::HALF_UP);
                                        $lines[$uuid]['unit_price'] = (string) $converted;
                                    }
                                }
                            }
                        }
                    }
                    $set($linesRepeaterName, $lines);
                }
            }
        });
    }

    public function exchangeRateFieldName(?string $name): static
    {
        $this->exchangeRateFieldName = $name;

        return $this;
    }

    public function getExchangeRateFieldName(): ?string
    {
        return $this->evaluate($this->exchangeRateFieldName);
    }

    public function linesRepeaterName(?string $name): static
    {
        $this->linesRepeaterName = $name;

        return $this;
    }

    public function getLinesRepeaterName(): ?string
    {
        return $this->evaluate($this->linesRepeaterName);
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }
}
