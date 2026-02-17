<?php

namespace Kezi\Pos\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Pos\Models\PosProfile;

class PosProfileFactory extends Factory
{
    protected $model = PosProfile::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->name.' POS',
            'type' => 'retail',
            'features' => [],
            'settings' => [],
            'is_active' => true,
            'stock_location_id' => null,
        ];
    }
}
