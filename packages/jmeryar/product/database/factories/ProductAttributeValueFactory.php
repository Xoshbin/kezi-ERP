<?php

namespace Jmeryar\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductAttributeValueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Jmeryar\Product\Models\ProductAttributeValue::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'product_attribute_id' => \Jmeryar\Product\Models\ProductAttribute::factory(),
            'name' => ['en' => $this->faker->word(), 'ar' => ''],
            'color_code' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
