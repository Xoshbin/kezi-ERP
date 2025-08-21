<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => null,
            'department_id' => null,
            'position_id' => null,
            'manager_id' => null,
            'employee_number' => $this->faker->unique()->numerify('EMP####'),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'date_of_birth' => $this->faker->date('Y-m-d', '2000-01-01'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'marital_status' => $this->faker->randomElement(['single', 'married', 'divorced', 'widowed']),
            'nationality' => 'Iraqi',
            'national_id' => $this->faker->numerify('#########'),
            'passport_number' => null,
            'address_line_1' => $this->faker->streetAddress,
            'address_line_2' => null,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'zip_code' => $this->faker->postcode,
            'country' => 'Iraq',
            'emergency_contact_name' => $this->faker->name,
            'emergency_contact_phone' => $this->faker->phoneNumber,
            'emergency_contact_relationship' => $this->faker->randomElement(['spouse', 'parent', 'sibling', 'friend']),
            'hire_date' => $this->faker->date('Y-m-d', 'now'),
            'termination_date' => null,
            'employment_status' => 'active',
            'employee_type' => $this->faker->randomElement(['full_time', 'part_time', 'contract', 'intern']),
            'bank_name' => $this->faker->company . ' Bank',
            'bank_account_number' => $this->faker->bankAccountNumber,
            'bank_routing_number' => $this->faker->numerify('###'),
            'is_active' => true,
        ];
    }

    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'terminated',
            'termination_date' => $this->faker->date('Y-m-d', 'now'),
            'is_active' => false,
        ]);
    }

    public function withDepartment(): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => Department::factory(),
        ]);
    }

    public function withPosition(): static
    {
        return $this->state(fn (array $attributes) => [
            'position_id' => Position::factory(),
        ]);
    }
}
