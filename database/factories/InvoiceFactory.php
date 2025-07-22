<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use App\Models\FiscalPosition;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory()->create()->id,
            'customer_id' => Partner::factory()->create()->id,
            'invoice_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'currency_id' => Currency::factory()->create()->id,
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'total_tax' => $this->faker->randomFloat(2, 0, 2000),
            'fiscal_position_id' => FiscalPosition::factory()->create()->id,
        ];
    }
}
