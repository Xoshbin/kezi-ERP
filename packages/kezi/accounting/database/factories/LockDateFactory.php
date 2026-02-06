<?php

namespace Kezi\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\Kezi\Accounting\Models\LockDate>
 */
class LockDateFactory extends Factory
{
    protected $model = \Kezi\Accounting\Models\LockDate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'lock_type' => 'tax_return_date',
            'locked_until' => now()->addDays(30),
        ];
    }
}
