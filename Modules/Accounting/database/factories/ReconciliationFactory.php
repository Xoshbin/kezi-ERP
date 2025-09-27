<?php

namespace Modules\Accounting\Database\Factories;

use App\Enums\Reconciliation\ReconciliationType;
use App\Models\Company;
use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reconciliation>
 */
class ReconciliationFactory extends Factory
{
    protected $model = Reconciliation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'reconciliation_type' => $this->faker->randomElement(ReconciliationType::cases()),
            'reconciled_by_user_id' => User::factory(),
            'reconciled_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'reference' => $this->faker->optional()->regexify('[A-Z]{3}-[0-9]{4}'),
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the reconciliation is for manual A/R and A/P.
     */
    public function manualArAp(): static
    {
        return $this->state(fn (array $attributes) => [
            'reconciliation_type' => ReconciliationType::ManualArAp,
        ]);
    }

    /**
     * Indicate that the reconciliation is for bank statements.
     */
    public function bankStatement(): static
    {
        return $this->state(fn (array $attributes) => [
            'reconciliation_type' => ReconciliationType::BankStatement,
        ]);
    }

    /**
     * Indicate that the reconciliation is for general manual reconciliation.
     */
    public function manualGeneral(): static
    {
        return $this->state(fn (array $attributes) => [
            'reconciliation_type' => ReconciliationType::ManualGeneral,
        ]);
    }

    /**
     * Indicate that the reconciliation has a reference.
     */
    public function withReference(?string $reference = null): static
    {
        return $this->state(fn (array $attributes) => [
            'reference' => $reference ?? $this->faker->regexify('[A-Z]{3}-[0-9]{4}'),
        ]);
    }

    /**
     * Indicate that the reconciliation has a description.
     */
    public function withDescription(?string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description ?? $this->faker->sentence(),
        ]);
    }
}
