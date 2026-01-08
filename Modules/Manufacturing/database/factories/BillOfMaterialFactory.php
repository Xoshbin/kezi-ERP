<?php

namespace Modules\Manufacturing\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Manufacturing\Enums\BOMType;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Product\Models\Product;

class BillOfMaterialFactory extends Factory
{
    protected $model = BillOfMaterial::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'product_id' => function (array $attributes) {
                return Product::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'code' => 'BOM-'.$this->faker->unique()->numberBetween(1000, 9999),
            'name' => ['en' => $this->faker->words(3, true)],
            'type' => BOMType::Normal,
            'quantity' => 1.0,
            'is_active' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
