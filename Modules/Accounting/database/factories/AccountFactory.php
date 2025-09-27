<?php

namespace Database\Factories;

use App\Enums\Accounting\AccountType;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Accounting\Models\Account::class;

    /**
     * Configure the model factory.
     *
     * This is the ideal place to handle logic that should run after
     * the definition() and any state() methods have been applied.
     * Here, we centralize the code generation logic.
     *
     * @return $this
     */
    public function configure(): static
    {
        return $this->afterMaking(function (\Modules\Accounting\Models\Account $account) {
            // If a code hasn't been explicitly set by a state, generate it
            // based on the account's final type. This is our single source of truth.
            if (empty($account->code)) {
                $typeValue = $account->type instanceof \Modules\Accounting\Enums\Accounting\AccountType
                    ? $account->type->value
                    : $account->type;
                $account->code = $this->generateCodeForType($typeValue);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Default to the five primary account types. Specific types are handled by states.
        $type = $this->faker->randomElement(['current_assets', 'current_liabilities', 'equity', 'income', 'expense']);

        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->words(2, true).' Account',
            'type' => $type,
            'is_deprecated' => false,
            'allow_reconciliation' => false, // Default to not allowing reconciliation for security
            // The 'code' will be generated in the configure() method.
        ];
    }

    /**
     * A single, private helper to generate account codes. No more duplication.
     */
    private function generateCodeForType(string $type): string
    {
        $prefix = match ($type) {
            'receivable', 'bank_and_cash', 'current_assets', 'non_current_assets', 'prepayments', 'fixed_assets' => $this->faker->numberBetween(1000, 1999),
            'payable', 'credit_card', 'current_liabilities', 'non_current_liabilities' => $this->faker->numberBetween(2000, 2999),
            'equity', 'current_year_earnings' => $this->faker->numberBetween(3000, 3999),
            'income', 'other_income' => $this->faker->numberBetween(4000, 4999),
            'expense', 'depreciation', 'cost_of_revenue' => $this->faker->numberBetween(5000, 5999),
            default => $this->faker->numberBetween(6000, 9999),
        };

        return $prefix.$this->faker->unique()->randomNumber(3, true);
    }

    // --- STATE METHODS ---
    // These are now much simpler. They only set the type and name.

    public function deprecated(): Factory
    {
        return $this->state(fn (array $attributes) => ['is_deprecated' => true]);
    }

    public function asset(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'current_assets']);
    }

    public function liability(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'current_liabilities']);
    }

    public function equity(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'equity']);
    }

    public function income(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'income']);
    }

    public function expense(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'expense']);
    }

    public function cash(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'bank_and_cash', 'name' => 'Cash Account']);
    }

    public function bank(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'bank_and_cash', 'name' => 'Bank Account']);
    }

    public function accountsReceivable(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'receivable', 'name' => 'Accounts Receivable']);
    }

    public function accountsPayable(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'payable', 'name' => 'Accounts Payable']);
    }

    /**
     * Indicate that the account allows reconciliation.
     */
    public function allowReconciliation(): Factory
    {
        return $this->state(fn (array $attributes) => ['allow_reconciliation' => true]);
    }
}
