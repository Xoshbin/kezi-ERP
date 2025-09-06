<?php

namespace Database\Factories;

use App\Enums\PaymentInstallments\InstallmentStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\PaymentInstallment;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentInstallment>
 */
class PaymentInstallmentFactory extends Factory
{
    protected $model = PaymentInstallment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->numberBetween(10000, 100000); // In minor units

        return [
            'company_id' => Company::factory(),
            'installment_type' => Invoice::class,
            'installment_id' => Invoice::factory(),
            'sequence' => 1,
            'due_date' => $this->faker->dateTimeBetween('now', '+90 days'),
            'amount' => $amount,
            'paid_amount' => 0,
            'status' => InstallmentStatus::Pending,
            'discount_percentage' => null,
            'discount_deadline' => null,
        ];
    }

    /**
     * Create a paid installment.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'paid_amount' => $attributes['amount'],
                'status' => InstallmentStatus::Paid,
            ];
        });
    }

    /**
     * Create a partially paid installment.
     */
    public function partiallyPaid(float $percentage = 0.5): static
    {
        return $this->state(function (array $attributes) use ($percentage) {
            $paidAmount = (int) ($attributes['amount'] * $percentage);
            return [
                'paid_amount' => $paidAmount,
                'status' => InstallmentStatus::PartiallyPaid,
            ];
        });
    }

    /**
     * Create an overdue installment.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'status' => InstallmentStatus::Pending,
        ]);
    }

    /**
     * Create an installment due soon.
     */
    public function dueSoon(int $days = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('now', "+{$days} days"),
            'status' => InstallmentStatus::Pending,
        ]);
    }

    /**
     * Add early payment discount.
     */
    public function withDiscount(float $discountPercentage = 2.0, int $discountDays = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => $discountPercentage,
            'discount_deadline' => now()->addDays($discountDays),
        ]);
    }

    /**
     * Set a specific amount.
     */
    public function amount(int $amountInMinorUnits): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amountInMinorUnits,
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

    /**
     * Set the due date.
     */
    public function dueDate(\DateTimeInterface $dueDate): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $dueDate,
        ]);
    }
}
