<?php

namespace Kezi\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Sales\Models\SalesOrder;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'customer_id' => \Kezi\Foundation\Models\Partner::factory(),
            'currency_id' => \Kezi\Foundation\Models\Currency::factory()->createSafely(),
            'created_by_user_id' => \App\Models\User::factory(),
            'so_number' => 'SO-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => \Kezi\Sales\Enums\Sales\SalesOrderStatus::Draft,
            'so_date' => now(),
            'total_amount' => 0,
            'total_tax' => 0,
        ];
    }
}
