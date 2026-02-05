<?php

namespace Kezi\Accounting\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Models\DunningLevel;

/**
 * @extends Factory<\Kezi\Accounting\Models\DunningLevel>
 */
class DunningLevelFactory extends Factory
{
    protected $model = DunningLevel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->words(2, true),
            'days_overdue' => $this->faker->numberBetween(1, 30),
            'email_subject' => $this->faker->sentence,
            'email_body' => $this->faker->paragraph,
            'print_letter' => false,
            'send_email' => true,
        ];
    }
}
