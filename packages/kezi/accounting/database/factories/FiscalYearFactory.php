<?php

namespace Kezi\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Enums\Accounting\FiscalYearState;
use Kezi\Accounting\Models\FiscalYear;

/**
 * @extends Factory<\Kezi\Accounting\Models\FiscalYear>
 */
class FiscalYearFactory extends Factory
{
    protected $model = FiscalYear::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-2 years', 'now');
        $startOfYear = (new \Carbon\Carbon($startDate))->startOfYear();
        $endOfYear = $startOfYear->copy()->endOfYear();

        return [
            'company_id' => Company::factory(),
            'name' => 'FY '.$startOfYear->format('Y'),
            'start_date' => $startOfYear,
            'end_date' => $endOfYear,
            'state' => FiscalYearState::Open,
            'closing_journal_entry_id' => null,
            'closed_by_user_id' => null,
            'closed_at' => null,
        ];
    }

    /**
     * Indicate that the fiscal year is in draft state.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => FiscalYearState::Draft,
        ]);
    }

    /**
     * Indicate that the fiscal year is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => FiscalYearState::Open,
        ]);
    }

    /**
     * Indicate that the fiscal year is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => FiscalYearState::Closed,
            'closed_at' => now(),
        ]);
    }

    /**
     * Configure for a specific year.
     */
    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "FY {$year}",
            'start_date' => \Carbon\Carbon::create($year, 1, 1),
            'end_date' => \Carbon\Carbon::create($year, 12, 31),
        ]);
    }
}
