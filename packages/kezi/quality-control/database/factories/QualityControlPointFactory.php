<?php

namespace Kezi\QualityControl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\QualityControl\Enums\QualityTriggerFrequency;
use Kezi\QualityControl\Enums\QualityTriggerOperation;
use Kezi\QualityControl\Models\QualityControlPoint;

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
