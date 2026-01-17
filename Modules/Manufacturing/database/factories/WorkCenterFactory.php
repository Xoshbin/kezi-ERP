<?php

namespace Modules\Manufacturing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Manufacturing\Models\WorkCenter;

class WorkCenterFactory extends Factory
{
    protected $model = WorkCenter::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'code' => 'WC-'.$this->faker->unique()->numberBetween(100, 999),
            'name' => ['en' => $this->faker->words(2, true)],
            'hourly_cost' => $this->faker->numberBetween(1000, 5000), // Minor units
            'currency_code' => function (array $attributes) {
                return \App\Models\Company::find($attributes['company_id'])?->currency?->code ?? 'USD';
            },
            'capacity' => $this->faker->randomFloat(2, 1, 10),
            'is_active' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
