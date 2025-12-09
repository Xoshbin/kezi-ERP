<?php

namespace Modules\Foundation\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Foundation\Enums\Partners\PartnerType;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = \Modules\Foundation\Models\Partner::class;

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
            'type' => $this->faker->randomElement([PartnerType::Vendor, PartnerType::Customer, PartnerType::Both]),
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

    public function vendor(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => PartnerType::Vendor,
        ]);
    }

    public function customer(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => PartnerType::Customer,
        ]);
    }
}
