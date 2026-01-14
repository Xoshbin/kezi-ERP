<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Enums\Inventory\SerialNumberStatus;
use Modules\Inventory\Models\SerialNumber;

/**
 * @extends Factory<SerialNumber>
 */
class SerialNumberFactory extends Factory
{
    protected $model = SerialNumber::class;

    public function definition(): array
    {
        return [
            'serial_code' => strtoupper($this->faker->bothify('SN-####-????')),
            'status' => SerialNumberStatus::Available,
            'warranty_start' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'warranty_end' => $this->faker->optional()->dateTimeBetween('now', '+2 years'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SerialNumberStatus::Available,
            'sold_to_partner_id' => null,
            'sold_at' => null,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SerialNumberStatus::Sold,
            'sold_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    public function defective(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SerialNumberStatus::Defective,
            'notes' => 'Defective: '.$this->faker->sentence(),
        ]);
    }

    public function withWarranty(int $months = 12): static
    {
        return $this->state(fn (array $attributes) => [
            'warranty_start' => now(),
            'warranty_end' => now()->addMonths($months),
        ]);
    }
}
