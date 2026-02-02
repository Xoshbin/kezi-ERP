<?php

namespace Kezi\Manufacturing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Manufacturing\Enums\WorkOrderStatus;
use Kezi\Manufacturing\Models\WorkOrder;

class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'manufacturing_order_id' => function (array $attributes) {
                return \Kezi\Manufacturing\Models\ManufacturingOrder::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'work_center_id' => function (array $attributes) {
                return \Kezi\Manufacturing\Models\WorkCenter::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'sequence' => 1,
            'name' => $this->faker->sentence(3),
            'status' => WorkOrderStatus::Pending,
            'planned_duration' => $this->faker->randomFloat(2, 1, 10),
        ];
    }
}
