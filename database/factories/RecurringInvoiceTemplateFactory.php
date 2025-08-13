<?php

namespace Database\Factories;

use App\Enums\RecurringInvoice\RecurringFrequency;
use App\Enums\RecurringInvoice\RecurringStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\RecurringInvoiceTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringInvoiceTemplate>
 */
class RecurringInvoiceTemplateFactory extends Factory
{
    protected $model = RecurringInvoiceTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory()->create();
        $targetCompany = Company::factory()->create();
        $currency = Currency::first() ?? Currency::factory()->create();
        $user = User::factory()->create();

        $incomeAccount = Account::factory()->income()->create([
            'company_id' => $company->id,
        ]);

        $expenseAccount = Account::factory()->expense()->create([
            'company_id' => $targetCompany->id,
        ]);

        return [
            'company_id' => $company->id,
            'target_company_id' => $targetCompany->id,
            'name' => $this->faker->words(3, true) . ' Fee',
            'description' => $this->faker->sentence(),
            'reference_prefix' => 'IC-RECURRING',
            'frequency' => $this->faker->randomElement(RecurringFrequency::cases()),
            'start_date' => now(),
            'end_date' => null,
            'next_run_date' => now()->addMonth(),
            'day_of_month' => $this->faker->numberBetween(1, 28),
            'month_of_quarter' => 1,
            'status' => RecurringStatus::Active,
            'is_active' => true,
            'currency_id' => $currency->id,
            'income_account_id' => $incomeAccount->id,
            'expense_account_id' => $expenseAccount->id,
            'tax_id' => null,
            'template_data' => [
                'lines' => [
                    [
                        'description' => $this->faker->words(3, true),
                        'quantity' => 1,
                        'unit_price' => [
                            'amount' => $this->faker->numberBetween(100000, 1000000), // $1000-$10000 in cents
                            'currency' => $currency->code,
                        ],
                        'product_id' => null,
                        'tax_id' => null,
                    ],
                ],
            ],
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => null,
            'last_generated_at' => null,
            'generation_count' => 0,
        ];
    }

    /**
     * Indicate that the template is due for generation.
     */
    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_run_date' => now()->subDay(),
            'status' => RecurringStatus::Active,
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the template is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecurringStatus::Paused,
        ]);
    }

    /**
     * Indicate that the template is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecurringStatus::Completed,
            'end_date' => now()->subDay(),
        ]);
    }

    /**
     * Set the template to monthly frequency.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => RecurringFrequency::Monthly,
        ]);
    }

    /**
     * Set the template to quarterly frequency.
     */
    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => RecurringFrequency::Quarterly,
        ]);
    }

    /**
     * Set the template to yearly frequency.
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => RecurringFrequency::Yearly,
        ]);
    }
}
