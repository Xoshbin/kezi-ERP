<?php

namespace Kezi\Manufacturing\Database\Factories;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Manufacturing\Models\BillOfMaterial;
use Kezi\Manufacturing\Models\BOMLine;
use Kezi\Product\Models\Product;

class BOMLineFactory extends Factory
{
    protected $model = BOMLine::class;

    public function definition(): array
    {
        $company = Company::factory()->create();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $currency = $company->currency;

        return [
            'company_id' => $company->id,
            'bom_id' => BillOfMaterial::factory()->create(['company_id' => $company->id])->id,
            'product_id' => $product->id,
            'quantity' => $this->faker->randomFloat(4, 1, 10),
            'unit_cost' => Money::ofMinor($this->faker->numberBetween(1000, 100000), $currency->code)->getMinorAmount()->toInt(),
            'currency_code' => $currency->code,
            'work_center_id' => null,
        ];
    }

    public function forBom(BillOfMaterial $bom): self
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $bom->company_id,
            'bom_id' => $bom->id,
        ]);
    }
}
