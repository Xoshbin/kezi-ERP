<?php

namespace Kezi\Foundation\Filament\Forms\Components;

use App\Models\Company;
use Filament\Facades\Filament;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Services\CurrencyConverterService;

class ExchangeRateInput extends TextInput
{
    protected ?string $currencyFieldName = 'currency_id';

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('accounting::currency.exchange_rate'))
            ->numeric()
            ->step(0.000001)
            ->minValue(0.000001)
            ->default(1.0)
            ->live()
            ->visible(function (callable $get) {
                $currencyId = $get($this->currencyFieldName);
                $company = Filament::getTenant();

                return $currencyId && $company instanceof Company && $currencyId != $company->currency_id;
            })
            ->helperText(function (callable $get) {
                $currencyId = $get($this->currencyFieldName);
                $company = Filament::getTenant();

                if ($currencyId && $company instanceof Company && $currencyId != $company->currency_id) {
                    $currency = Currency::find($currencyId);
                    if ($currency instanceof Currency) {
                        $service = app(CurrencyConverterService::class);

                        $latestRate = $service->getLatestExchangeRate($currency, $company);

                        if ($latestRate) {
                            return __('accounting::currency.exchange_rates.rate_helper') . ' (' . __('accounting::currency.exchange_rates.rate') . ': ' . $latestRate . ')';
                        }
                    }
                }

                return __('accounting::currency.exchange_rates.rate_helper');
            })
            ->hintAction(function () {
                $action = DocsAction::make('understanding-currencies');

                // Convert general Action to Form Component Action if needed, 
                // but DocsAction::make returns a Filament\Actions\Action.
                
                return Action::make('understanding_currencies_docs')
                    ->icon('heroicon-o-information-circle')
                    ->color('gray')
                    ->url($action->getUrl())
                    ->hiddenLabel()
                    ->openUrlInNewTab();
            });
    }

    public function currencyField(string $name): static
    {
        $this->currencyFieldName = $name;

        return $this;
    }
}
