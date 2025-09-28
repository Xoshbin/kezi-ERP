<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;
use Modules\Accounting\Models\LockDate;

/**
 * @extends Factory<LockDate>
 */
class LockDateFactory extends Factory
{
    protected $model = \Modules\Accounting\Models\LockDate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory()->create()->id,
            'lock_type' => $this->faker->randomElement(['tax_return_date', 'everything_date']),
            'locked_until' => $this->faker->dateTimeBetween('now', '+1 year'),
        ];
    }
}
