<?php

namespace Kezi\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Sales\Enums\Sales\QuoteStatus;
use Kezi\Sales\Models\Quote;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'partner_id' => \Kezi\Foundation\Models\Partner::factory(),
            'currency_id' => \Kezi\Foundation\Models\Currency::factory()->createSafely(),
            'created_by_user_id' => \App\Models\User::factory(),
            'quote_number' => 'QT-'.$this->faker->unique()->numberBetween(10000, 99999),
            'quote_date' => now(),
            'valid_until' => now()->addDays(30),
            'status' => QuoteStatus::Draft,
            'version' => 1,
            'exchange_rate' => 1.0,
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'total' => 0,
        ];
    }

    /**
     * Indicate that the quote is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuoteStatus::Draft,
        ]);
    }

    /**
     * Indicate that the quote has been sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuoteStatus::Sent,
        ]);
    }

    /**
     * Indicate that the quote has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuoteStatus::Accepted,
        ]);
    }

    /**
     * Indicate that the quote has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuoteStatus::Rejected,
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the quote has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuoteStatus::Expired,
            'valid_until' => now()->subDays(1),
        ]);
    }

    /**
     * Indicate that the quote has been converted.
     */
    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuoteStatus::Converted,
            'converted_at' => now(),
        ]);
    }

    /**
     * Indicate that the quote has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuoteStatus::Cancelled,
        ]);
    }

    /**
     * Set this as a revision of another quote.
     */
    public function revision(int $version = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => $version,
        ]);
    }
}
