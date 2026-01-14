<?php

namespace Modules\Purchase\Database\Factories;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Models\Partner;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;

/**
 * @extends Factory<VendorBill>
 */
class VendorBillFactory extends Factory
{
    protected $model = \Modules\Purchase\Models\VendorBill::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'vendor_id' => Partner::factory(),
            'currency_id' => function (array $attributes) {
                return Company::find($attributes['company_id'])->currency_id;
            },
            'bill_date' => $this->faker->date(),
            'accounting_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'bill_reference' => $this->faker->unique()->bothify('BILL-####??'),
            'status' => 'draft',
            'total_amount' => function (array $attributes) {
                $currencyCode = \Modules\Foundation\Models\Currency::find($attributes['currency_id'])->code;

                return Money::of(0, $currencyCode);
            },
            'total_tax' => function (array $attributes) {
                $currencyCode = \Modules\Foundation\Models\Currency::find($attributes['currency_id'])->code;

                return Money::of(0, $currencyCode);
            },
            'journal_entry_id' => null,
            'posted_at' => null,
            'reset_to_draft_log' => null,
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'posted_at' => null,
            'journal_entry_id' => null,
        ]);
    }

    public function posted(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'posted',
                'posted_at' => now(),
                'journal_entry_id' => JournalEntry::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id,
            ];
        });
    }

    public function withLines(int $count = 1): self
    {
        return $this->afterCreating(function (\Modules\Purchase\Models\VendorBill $vendorBill) use ($count) {
            VendorBillLine::factory()->count($count)->create([
                'vendor_bill_id' => $vendorBill->id,
            ]);
        });
    }
}
