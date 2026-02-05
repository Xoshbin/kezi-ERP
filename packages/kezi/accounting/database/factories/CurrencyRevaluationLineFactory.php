<?php

namespace Kezi\Accounting\Database\Factories;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\CurrencyRevaluation;
use Kezi\Accounting\Models\CurrencyRevaluationLine;
use Kezi\Foundation\Models\Currency;

/**
 * @extends Factory<\Kezi\Accounting\Models\CurrencyRevaluationLine>
 */
class CurrencyRevaluationLineFactory extends Factory
{
    protected $model = CurrencyRevaluationLine::class;

    public function definition(): array
    {
        $revaluation = CurrencyRevaluation::factory()->create();
        $currency = Currency::factory()->createSafely();

        return [
            'currency_revaluation_id' => $revaluation->id,
            'account_id' => Account::factory(),
            'currency_id' => $currency->id,
            'foreign_currency_balance' => Money::of(100, $currency->code),
            'historical_rate' => 1.0,
            'current_rate' => 1.1,
            'book_value' => Money::of(100, $revaluation->company->currency->code),
            'revalued_amount' => Money::of(110, $revaluation->company->currency->code),
            'adjustment_amount' => Money::of(10, $revaluation->company->currency->code),
        ];
    }
}
