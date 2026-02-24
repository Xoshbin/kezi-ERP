<?php

namespace Kezi\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Product\Models\ProductCategory;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->text(20),
            'parent_id' => null,
        ];
    }
}
