<?php

namespace Modules\Foundation\Filament\Forms\Components;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Modules\Foundation\Models\Currency;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Brick\Math\Exception\RoundingNecessaryException;

class MoneyInput extends TextInput
{
    protected ?Money $originalMoneyObject = null;

    // This will hold the name of the field that contains the currency ID.
    protected ?string $currencyFieldName = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->inputMode('decimal')
            // Don't use ->numeric() as it conflicts with Money objects
            ->rule('numeric')
            ->afterStateHydrated(function (MoneyInput $component, $state) {
                if ($state instanceof Money) {
                    // This still works perfectly for the initial load on the edit page.
                    $component->setOriginalMoneyObject($state);
                }
            })
            ->formatStateUsing(function ($state): ?string {
                if ($state instanceof Money) {
                    return $state->getAmount()->__toString();
                }

                return $state;
            })
            ->dehydrateStateUsing(fn($state) => $state)

            // Pass the `Get` helper to your closures
            ->prefix(function (MoneyInput $component, Get $get) {
                $money = $this->getMoneyObject($component, $get);

                if ($money instanceof Money) {
                    return $money->getCurrency()->getCurrencyCode();
                }

                // New fallback logic for the Create page before a value is entered.
                if ($this->currencyFieldName) {
                    /** @var int|null $currencyId */
                    $currencyId = $get($this->currencyFieldName);
                    if ($currencyId) {
                        $currency = Currency::find($currencyId);
                        if ($currency) {
                            return $currency->code;
                        }
                    }
                }

                // Original fallback for the Edit page.
                $livewire = $component->getLivewire();
                if (method_exists($livewire, 'getRecord')) {
                    $mainRecord = $livewire->getRecord();
                    if ($mainRecord instanceof Model && method_exists($mainRecord, 'currency')) {
                        $currency = $mainRecord->relationLoaded('currency') ? $mainRecord->getRelation('currency') : $mainRecord->currency()->first();
                        /** @var Currency|null $currency */
                        if ($currency) {
                            return $currency->code;
                        }
                    }
                }

                // Try to get the tenant's default currency as final fallback
                $tenant = Filament::getTenant();
                if ($tenant instanceof Model && method_exists($tenant, 'currency')) {
                    /** @var Currency|null $tCurrency */
                    $tCurrency = $tenant->relationLoaded('currency') ? $tenant->getRelation('currency') : $tenant->currency()->first();
                    if ($tCurrency) {
                        return $tCurrency->code;
                    }
                }

                return '$'; // Default fallback
            })

            // Pass the `Get` helper to your closures
            ->helperText(function (MoneyInput $component, Get $get) {
                $money = $this->getMoneyObject($component, $get);

                if ($money instanceof Money) {
                    /** @var Money $money */
                    return $money->formatTo('EN_us');
                }

                return null;
            });
    }

    // Add this new public method
    public function currencyField(string $name): static
    {
        $this->currencyFieldName = $name;
        // Make the component reactive to changes in the specified currency field.
        $this->live();

        return $this;
    }

    /**
     * Updated central method to get or create the Money object.
     * It now accepts the `Get` helper.
     */
    protected function getMoneyObject(MoneyInput $component, Get $get): ?Money
    {
        // First, try to get the cached object from afterStateHydrated (for Edit page load).
        $money = $component->getOriginalMoneyObject();
        if ($money instanceof Money) {
            return $money;
        }

        $state = $component->getState();
        $currencyCode = null;

        // Strategy 1: Use the configured currency field from the form state (`$get`).
        // This is for the Create page or live updates.
        if ($this->currencyFieldName) {
            $currencyId = $get($this->currencyFieldName);
            if ($currencyId) {
                // To avoid too many database queries, we can cache the result.
                static $currencyCache = [];
                if (! isset($currencyCache[$currencyId])) {
                    $currencyCache[$currencyId] = Currency::find($currencyId);
                }
                $currency = $currencyCache[$currencyId];
                if ($currency) {
                    $currencyCode = $currency->code;
                }
            }
        }

        // Strategy 2: Fallback for the Edit page if the first strategy fails.
        if (! $currencyCode) {
            $livewire = $component->getLivewire();
            if (method_exists($livewire, 'getRecord')) {
                $mainRecord = $livewire->getRecord();
                if ($mainRecord instanceof Model && method_exists($mainRecord, 'currency')) {
                    $currency = $mainRecord->relationLoaded('currency') ? $mainRecord->getRelation('currency') : $mainRecord->currency()->first();
                    /** @var Currency|null $currency */
                    if ($currency) {
                        $currencyCode = $currency->code;
                    }
                }
            }
        }

        // If we have an amount and a currency, create the Money object.
        if (is_numeric($state) && $currencyCode) {
            // Use Money::of with rounding mode to handle precision issues
            try {
                return Money::of($state, $currencyCode);
            } catch (RoundingNecessaryException) {
                // If rounding is necessary, use the default rounding mode (HALF_UP)
                return Money::of($state, $currencyCode, null, RoundingMode::HALF_UP);
            }
        }

        return null;
    }

    public function setOriginalMoneyObject(Money $money): static
    {
        $this->originalMoneyObject = $money;

        return $this;
    }

    public function getOriginalMoneyObject(): ?Money
    {
        return $this->originalMoneyObject;
    }
}
