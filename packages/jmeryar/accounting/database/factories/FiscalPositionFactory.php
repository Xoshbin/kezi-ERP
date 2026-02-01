<?php

namespace Jmeryar\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Accounting\Models\FiscalPosition;

/**
 * @extends Factory<FiscalPosition>
 */
class FiscalPositionFactory extends Factory
{
    protected $model = FiscalPosition::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->company,
            'country' => $this->faker->countryCode,
            'auto_apply' => false,
            'vat_required' => false,
            'zip_from' => null,
            'zip_to' => null,
        ];
    }
}
