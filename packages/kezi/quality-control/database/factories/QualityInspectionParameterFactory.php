<?php

namespace Kezi\QualityControl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\QualityControl\Enums\QualityCheckType;
use Kezi\QualityControl\Models\QualityInspectionParameter;

/**
 * @extends Factory<QualityInspectionParameter>
 */
class QualityInspectionParameterFactory extends Factory
{
    protected $model = QualityInspectionParameter::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'check_type' => QualityCheckType::PassFail,
            'sequence' => 0,
            'instructions' => $this->faker->sentence(),
        ];
    }
}
