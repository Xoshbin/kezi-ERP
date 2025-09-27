<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Department;
use App\Models\Position;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = \Modules\Foundation\Models\Currency::firstOrCreate(
            ['code' => 'IQD'],
            [
                'name' => 'Iraqi Dinar',
                'symbol' => 'IQD',
                'is_active' => true,
                'decimal_places' => 3,
            ]
        );

        $minSalary = $this->faker->numberBetween(500000, 1000000);
        $maxSalary = $this->faker->numberBetween($minSalary + 200000, $minSalary + 800000);

        return [
            'company_id' => Company::factory(),
            'department_id' => null,
            'title' => [
                'en' => $this->faker->jobTitle,
                'ku' => $this->faker->jobTitle, // In a real app, you'd have proper Kurdish translations
            ],
            'description' => $this->faker->paragraph,
            'requirements' => [
                'en' => $this->faker->sentences(3, true),
                'ku' => $this->faker->sentences(3, true),
            ],
            'responsibilities' => [
                'en' => $this->faker->sentences(4, true),
                'ku' => $this->faker->sentences(4, true),
            ],
            'employment_type' => $this->faker->randomElement(['full_time', 'part_time', 'contract', 'intern']),
            'level' => $this->faker->randomElement(['entry', 'junior', 'mid', 'senior', 'lead', 'manager', 'director']),
            'min_salary' => Money::of($minSalary, $currency->code),
            'max_salary' => Money::of($maxSalary, $currency->code),
            'currency_id' => $currency->id,
            'is_active' => true,
        ];
    }

    public function withDepartment(): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => Department::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
