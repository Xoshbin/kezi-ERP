<?php

namespace Modules\Manufacturing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Manufacturing\Enums\WorkOrderStatus;
use Modules\Manufacturing\Models\WorkOrder;

class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'manufacturing_order_id' => 1,
            'work_center_id' => 1,
            'sequence' => 1,
            'name' => $this->faker->sentence(3),
            'status' => WorkOrderStatus::Pending,
            'planned_duration' => $this->faker->randomFloat(2, 1, 10),
        ];
    }
}
