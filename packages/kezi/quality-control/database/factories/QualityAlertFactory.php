<?php

namespace Kezi\QualityControl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\QualityControl\Enums\QualityAlertStatus;
use Kezi\QualityControl\Models\QualityAlert;

/**
 * @extends Factory<\Kezi\QualityControl\Models\QualityAlert>
 */
class QualityAlertFactory extends Factory
{
    protected $model = QualityAlert::class;

    public function definition(): array
    {
        return [
            'number' => 'QA-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => QualityAlertStatus::New,
            'description' => $this->faker->sentence(),
            // Assuming relationships will be handled by states or explicitly
            'product_id' => \Kezi\Product\Models\Product::factory(),
            'defect_type_id' => \Kezi\QualityControl\Models\DefectType::factory(),
            'company_id' => \App\Models\Company::factory(),
            'reported_by_user_id' => \App\Models\User::factory(),
        ];
    }
}
