<?php

namespace Kezi\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductAttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Kezi\Product\Models\ProductAttribute::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => ['en' => $this->faker->word(), 'ar' => ''],
            'type' => 'select',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
