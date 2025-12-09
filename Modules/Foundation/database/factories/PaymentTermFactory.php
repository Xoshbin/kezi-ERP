<?php

namespace Modules\Foundation\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Foundation\Enums\PaymentTerms\PaymentTermType;
use Modules\Foundation\Models\PaymentTerm;

/**
 * @extends Factory<\Modules\Foundation\Models\PaymentTerm>
 */
class PaymentTermFactory extends Factory
{
    protected $model = PaymentTerm::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => [
                'en' => $this->faker->randomElement([
                    'Immediate Payment',
                    'Net 15',
                    'Net 30',
                    'Net 60',
                    'End of Month',
                    'End of Month + 30',
                    '2% 10, Net 30',
                    '50% in 30 days, 50% in 60 days',
                ]),
                'ar' => $this->faker->randomElement([
                    'دفع فوري',
                    'صافي 15',
                    'صافي 30',
                    'صافي 60',
                    'نهاية الشهر',
                    'نهاية الشهر + 30',
                    '2% خلال 10، صافي 30',
                    '50% خلال 30 يوم، 50% خلال 60 يوم',
                ]),
            ],
            'description' => [
                'en' => $this->faker->optional()->sentence(),
                'ar' => $this->faker->optional()->sentence(),
            ],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the payment term is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an immediate payment term.
     */
    public function immediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => [
                'en' => 'Immediate Payment',
                'ar' => 'دفع فوري',
            ],
        ])->afterCreating(function (PaymentTerm $paymentTerm) {
            $paymentTerm->lines()->create([
                'sequence' => 1,
                'type' => PaymentTermType::Immediate,
                'days' => 0,
                'percentage' => 100,
            ]);
        });
    }

    /**
     * Create a net 30 payment term.
     */
    public function net30(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => [
                'en' => 'Net 30',
                'ar' => 'صافي 30',
            ],
        ])->afterCreating(function (PaymentTerm $paymentTerm) {
            $paymentTerm->lines()->create([
                'sequence' => 1,
                'type' => PaymentTermType::Net,
                'days' => 30,
                'percentage' => 100,
            ]);
        });
    }

    /**
     * Create a payment term with early payment discount.
     */
    public function withDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => [
                'en' => '2% 10, Net 30',
                'ar' => '2% خلال 10، صافي 30',
            ],
        ])->afterCreating(function (PaymentTerm $paymentTerm) {
            $paymentTerm->lines()->create([
                'sequence' => 1,
                'type' => PaymentTermType::Net,
                'days' => 30,
                'percentage' => 100,
                'discount_percentage' => 2.0,
                'discount_days' => 10,
            ]);
        });
    }

    /**
     * Create an installment payment term.
     */
    public function installments(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => [
                'en' => '50% in 30 days, 50% in 60 days',
                'ar' => '50% خلال 30 يوم، 50% خلال 60 يوم',
            ],
        ])->afterCreating(function (PaymentTerm $paymentTerm) {
            $paymentTerm->lines()->createMany([
                [
                    'sequence' => 1,
                    'type' => PaymentTermType::Net,
                    'days' => 30,
                    'percentage' => 50,
                ],
                [
                    'sequence' => 2,
                    'type' => PaymentTermType::Net,
                    'days' => 60,
                    'percentage' => 50,
                ],
            ]);
        });
    }
}
