<?php

namespace Modules\QualityControl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\QualityControl\Enums\QualityTriggerFrequency;
use Modules\QualityControl\Enums\QualityTriggerOperation;
use Modules\QualityControl\Models\QualityControlPoint;

/**
 * @extends Factory<QualityControlPoint>
 */
class QualityControlPointFactory extends Factory
{
    protected $model = QualityControlPoint::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
            'trigger_frequency' => QualityTriggerFrequency::PerOperation,
            'is_blocking' => false,
            'active' => true,
        ];
    }
}
