<?php

namespace Modules\Foundation\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Foundation\Models\PaymentTerm;
use Modules\Foundation\Models\PaymentTermLine;

/**
 * @extends Factory<\App\Models\PaymentTermLine>
 */
class PaymentTermLineFactory extends Factory
{
    protected $model = PaymentTermLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_term_id' => PaymentTerm::factory(),
            'sequence' => 1,
            'type' => $this->faker->randomElement(\Modules\Foundation\Enums\PaymentTerms\PaymentTermType::cases()),
            'days' => $this->faker->numberBetween(0, 90),
            'percentage' => 100.0,
            'day_of_month' => null,
            'discount_percentage' => null,
            'discount_days' => null,
        ];
    }

    /**
     * Create an immediate payment line.
     */
    public function immediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::Immediate,
            'days' => 0,
            'percentage' => 100.0,
        ]);
    }

    /**
     * Create a net payment line.
     */
    public function net(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::Net,
            'days' => $days,
            'percentage' => 100.0,
        ]);
    }

    /**
     * Create an end of month payment line.
     */
    public function endOfMonth(int $additionalDays = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::EndOfMonth,
            'days' => $additionalDays,
            'percentage' => 100.0,
        ]);
    }

    /**
     * Create a day of month payment line.
     */
    public function dayOfMonth(int $dayOfMonth = 15, int $additionalDays = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::DayOfMonth,
            'days' => $additionalDays,
            'day_of_month' => $dayOfMonth,
            'percentage' => 100.0,
        ]);
    }

    /**
     * Add early payment discount.
     */
    public function withDiscount(float $discountPercentage = 2.0, int $discountDays = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => $discountPercentage,
            'discount_days' => $discountDays,
        ]);
    }

    /**
     * Set the percentage for installment payments.
     */
    public function percentage(float $percentage): static
    {
        return $this->state(fn (array $attributes) => [
            'percentage' => $percentage,
        ]);
    }

    /**
     * Set the sequence number.
     */
    public function withSequence(int $sequence): static
    {
        return $this->state(fn (array $attributes) => [
            'sequence' => $sequence,
        ]);
    }
}
