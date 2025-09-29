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
            'company_id' => Company::factory(),
            'lock_type' => 'tax_return_date',
            'locked_until' => now()->addDays(30),
        ];
    }
}
