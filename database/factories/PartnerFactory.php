<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Partner>
 */
class PartnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory()->create()->id,
            'name' => $this->faker->company,
            'type' => $this->faker->randomElement([Partner::TYPE_VENDOR, Partner::TYPE_CUSTOMER, Partner::TYPE_BOTH]),
            'contact_person' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'address_line_1' => $this->faker->streetAddress,
            'address_line_2' => $this->faker->optional()->streetAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->city,
            'zip_code' => $this->faker->postcode,
            'country' => $this->faker->country,
            'tax_id' => $this->faker->optional()->bothify('??########'),
            'is_active' => $this->faker->boolean,
        ];
    }
}
