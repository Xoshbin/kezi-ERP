<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        // Define common account types as per accounting principles
        $accountTypes = [
            'Asset', 'Liability', 'Equity', 'Income', 'Expense',
            'Bank and Cash', 'Receivable', 'Payable', 'Current Assets',
            'Non-current Assets', 'Prepayments', 'Fixed Assets', 'Credit Card',
            'Current Liabilities', 'Non-current Liabilities', 'Other Income',
            'Depreciation', 'Cost of Revenue', 'Other', 'Off-Balance Sheet'
        ];

        // Randomly pick an account type
        $type = $this->faker->randomElement($accountTypes);

        // Generate a plausible account code based on type for better realism
        // This simulates common accounting practices where codes follow patterns [9, 10]
        $codePrefix = '';
        switch ($type) {
            case 'Asset':
            case 'Cash':
            case 'Bank and Cash':
            case 'Receivable':
            case 'Current Assets':
            case 'Non-current Assets':
            case 'Prepayments':
            case 'Fixed Assets':
                $codePrefix = $this->faker->numberBetween(100, 199); // Assets usually start with 1xx [10, 11]
                break;
            case 'Liability':
            case 'Payable':
            case 'Credit Card':
            case 'Current Liabilities':
            case 'Non-current Liabilities':
                $codePrefix = $this->faker->numberBetween(200, 299); // Liabilities usually start with 2xx [10, 11]
                break;
            case 'Equity':
                $codePrefix = $this->faker->numberBetween(300, 399); // Equity usually starts with 3xx [10, 11]
                break;
            case 'Income':
            case 'Other Income':
                $codePrefix = $this->faker->numberBetween(400, 499); // Revenues/Income usually start with 4xx [10, 11]
                break;
            case 'Expense':
            case 'Depreciation':
            case 'Cost of Revenue':
                $codePrefix = $this->faker->numberBetween(500, 599); // Expenses usually start with 5xx [10, 11]
                break;
            default:
                $codePrefix = $this->faker->numberBetween(600, 999); // Other/Misc [5]
                break;
        }

        return [
            // Ensure a company_id is always present. Use an existing company or create one.
            'company_id' => Company::factory(), // Automatically creates a Company if none is provided [12, 13]
            'name' => $this->faker->words(2, true) . ' Account', // e.g., "Sales Revenue Account"
            'code' => $codePrefix . $this->faker->unique()->randomNumber(4, true), // Ensures unique code [7]
            'type' => $type,
            'is_deprecated' => false, // Default to not deprecated
        ];
    }

    /**
     * Indicate that the account is deprecated.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function deprecated()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_deprecated' => true,
            ];
        });
    }

    /**
     * Define specific account types states.
     * This provides convenience for creating common accounting accounts directly.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function asset()
    {
        return $this->state(fn (array $attributes) => ['type' => 'Asset', 'code' => $this->faker->numberBetween(100, 199) . $this->faker->unique()->randomNumber(4, true)]);
    }

    public function liability()
    {
        return $this->state(fn (array $attributes) => ['type' => 'Liability', 'code' => $this->faker->numberBetween(200, 299) . $this->faker->unique()->randomNumber(4, true)]);
    }

    public function equity()
    {
        return $this->state(fn (array $attributes) => ['type' => 'Equity', 'code' => $this->faker->numberBetween(300, 399) . $this->faker->unique()->randomNumber(4, true)]);
    }

    public function income()
    {
        return $this->state(fn (array $attributes) => ['type' => 'Income', 'code' => $this->faker->numberBetween(400, 499) . $this->faker->unique()->randomNumber(4, true)]);
    }

    public function expense()
    {
        return $this->state(fn (array $attributes) => ['type' => 'Expense', 'code' => $this->faker->numberBetween(500, 599) . $this->faker->unique()->randomNumber(4, true)]);
    }

    public function cash()
    {
        return $this->state(fn (array $attributes) => ['type' => 'Bank and Cash', 'name' => 'Cash Account', 'code' => '100' . $this->faker->unique()->randomNumber(4, true)]);
    }

    public function bank()
    {
        return $this->state(fn (array $attributes) => ['type' => 'Bank and Cash', 'name' => 'Bank Account', 'code' => '101' . $this->faker->unique()->randomNumber(4, true)]);
    }

    public function accountsReceivable()
    {
        return $this->state(fn (array $attributes) => ['type' => 'Receivable', 'name' => 'Accounts Receivable', 'code' => '113' . $this->faker->unique()->randomNumber(4, true)]);
    }

    public function accountsPayable()
    {
        return $this->state(fn (array $attributes) => ['type' => 'Payable', 'name' => 'Accounts Payable', 'code' => '212' . $this->faker->unique()->randomNumber(4, true)]);
    }
}
