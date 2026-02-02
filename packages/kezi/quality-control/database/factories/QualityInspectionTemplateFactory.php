<?php

namespace Kezi\QualityControl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\QualityControl\Models\QualityInspectionTemplate;

/**
 * @extends Factory<QualityInspectionTemplate>
 */
class QualityInspectionTemplateFactory extends Factory
{
    protected $model = QualityInspectionTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'active' => true,
        ];
    }
}
