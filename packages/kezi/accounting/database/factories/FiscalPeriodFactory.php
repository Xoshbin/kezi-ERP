<?php

namespace Kezi\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Enums\Accounting\FiscalPeriodState;
use Kezi\Accounting\Models\FiscalPeriod;
use Kezi\Accounting\Models\FiscalYear;

/**
 * @extends Factory<\Kezi\Accounting\Models\FiscalPeriod>
 */
class FiscalPeriodFactory extends Factory
{
    protected $model = FiscalPeriod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $startOfMonth = (new \Carbon\Carbon($startDate))->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        return [
            'fiscal_year_id' => FiscalYear::factory(),
            'name' => $startOfMonth->format('F Y'),
            'start_date' => $startOfMonth,
            'end_date' => $endOfMonth,
            'state' => FiscalPeriodState::Open,
        ];
    }

    /**
     * Indicate that the fiscal period is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => FiscalPeriodState::Open,
        ]);
    }

    /**
     * Indicate that the fiscal period is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => FiscalPeriodState::Closed,
        ]);
    }
}
