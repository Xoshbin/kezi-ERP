<?php

namespace Kezi\Accounting\Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Enums\Currency\RevaluationStatus;
use Kezi\Accounting\Models\CurrencyRevaluation;

/**
 * @extends Factory<CurrencyRevaluation>
 */
class CurrencyRevaluationFactory extends Factory
{
    protected $model = CurrencyRevaluation::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'created_by_user_id' => User::factory(),
            'revaluation_date' => $this->faker->date(),
            'description' => $this->faker->sentence(),
            'status' => RevaluationStatus::Draft,
            'posted_at' => null,
            'total_gain' => 0,
            'total_loss' => 0,
            'net_adjustment' => 0,
        ];
    }
}
