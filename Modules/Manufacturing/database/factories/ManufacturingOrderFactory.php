<?php

namespace Modules\Manufacturing\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\StockLocation;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Product\Models\Product;

class ManufacturingOrderFactory extends Factory
{
    protected $model = ManufacturingOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'number' => 'MO-'.$this->faker->unique()->numberBetween(1000, 9999),
            'bom_id' => function (array $attributes) {
                return BillOfMaterial::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'product_id' => function (array $attributes) {
                // Get the product from the BOM
                $bom = BillOfMaterial::find($attributes['bom_id']);

                return $bom ? $bom->product_id : Product::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'status' => ManufacturingOrderStatus::Draft,
            'quantity_to_produce' => $this->faker->randomFloat(4, 1, 100),
            'quantity_produced' => 0,
            'source_location_id' => function (array $attributes) {
                return StockLocation::factory()->create([
                    'company_id' => $attributes['company_id'],
                    'type' => 'internal',
                ])->id;
            },
            'destination_location_id' => function (array $attributes) {
                return StockLocation::factory()->create([
                    'company_id' => $attributes['company_id'],
                    'type' => 'internal',
                ])->id;
            },
            'planned_start_date' => now(),
            'planned_end_date' => now()->addDays(7),
        ];
    }
}
