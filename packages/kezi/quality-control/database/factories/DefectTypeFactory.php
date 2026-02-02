<?php

namespace Kezi\QualityControl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\QualityControl\Models\DefectType;

/**
 * @extends Factory<DefectType>
 */
class DefectTypeFactory extends Factory
{
    protected $model = DefectType::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('DEF-???')),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'active' => true,
            'company_id' => \App\Models\Company::factory(),
        ];
    }
}
