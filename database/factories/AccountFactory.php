<?php

namespace Database\Factories;

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
    protected $model = Account::class;

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
        return $this->afterMaking(function (Account $account) {
            // If a code hasn't been explicitly set by a state, generate it
            // based on the account's final type. This is our single source of truth.
            if (empty($account->code)) {
                $account->code = $this->generateCodeForType($account->type);
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
        $type = $this->faker->randomElement(['Asset', 'Liability', 'Equity', 'Income', 'Expense']);

        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->words(2, true) . ' Account',
            'type' => $type,
            'is_deprecated' => false,
            // The 'code' will be generated in the configure() method.
        ];
    }

    /**
     * A single, private helper to generate account codes. No more duplication.
     */
    private function generateCodeForType(string $type): string
    {
        $prefix = match ($type) {
            'Asset', 'Bank and Cash', 'Receivable', 'Current Assets', 'Non-current Assets', 'Prepayments', 'Fixed Assets' => $this->faker->numberBetween(1000, 1999),
            'Liability', 'Payable', 'Credit Card', 'Current Liabilities', 'Non-current Liabilities' => $this->faker->numberBetween(2000, 2999),
            'Equity' => $this->faker->numberBetween(3000, 3999),
            'Income', 'Other Income' => $this->faker->numberBetween(4000, 4999),
            'Expense', 'Depreciation', 'Cost of Revenue' => $this->faker->numberBetween(5000, 5999),
            default => $this->faker->numberBetween(6000, 9999),
        };

        return $prefix . $this->faker->unique()->randomNumber(3, true);
    }

    // --- STATE METHODS ---
    // These are now much simpler. They only set the type and name.

    public function deprecated(): Factory
    {
        return $this->state(fn (array $attributes) => ['is_deprecated' => true]);
    }

    public function asset(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'Asset']);
    }

    public function liability(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'Liability']);
    }

    public function equity(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'Equity']);
    }

    public function income(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'Income']);
    }

    public function expense(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'Expense']);
    }

    public function cash(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'Bank and Cash', 'name' => 'Cash Account']);
    }

    public function bank(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'Bank and Cash', 'name' => 'Bank Account']);
    }

    public function accountsReceivable(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'Receivable', 'name' => 'Accounts Receivable']);
    }

    public function accountsPayable(): Factory
    {
        return $this->state(fn (array $attributes) => ['type' => 'Payable', 'name' => 'Accounts Payable']);
    }
}
