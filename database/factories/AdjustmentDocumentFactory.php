<?php

namespace Database\Factories;

use Brick\Money\Money;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdjustmentDocument>
 */
class AdjustmentDocumentFactory extends Factory
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
            'currency_id' => function (array $attributes) {
                return Company::find($attributes['company_id'])->currency_id;
            },
            'original_invoice_id' => null,
            'original_vendor_bill_id' => null,
            'type' => $this->faker->randomElement(['Credit Note', 'Debit Note', 'Miscellaneous Adjustment']),
            'date' => $this->faker->date(),
            'reference_number' => $this->faker->unique()->bothify('ADJ-#####'),
            'total_amount' => function (array $attributes) {
                $currency = Currency::find($attributes['currency_id']);
                return Money::of($this->faker->randomFloat(2, 100, 10000), $currency->code);
            },
            'total_tax' => function (array $attributes) {
                $currency = Currency::find($attributes['currency_id']);
                return Money::of($this->faker->randomFloat(2, 0, 2000), $currency->code);
            },
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['Draft', 'Posted']),
            'journal_entry_id' => null,
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Draft',
            'posted_at' => null,
            'journal_entry_id' => null,
        ]);
    }

    public function posted(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'Posted',
                'posted_at' => now(),
                'journal_entry_id' => \App\Models\JournalEntry::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id,
            ];
        });
    }

    public function withLines(int $count = 1): self
    {
        return $this->afterCreating(function (\App\Models\AdjustmentDocument $adjustmentDocument) use ($count) {
            \App\Models\AdjustmentDocumentLine::factory()->count($count)->create([
                'adjustment_document_id' => $adjustmentDocument->id,
            ]);

            // Recalculate totals from lines
            $adjustmentDocument->refresh();
            $adjustmentDocument->calculateTotalsFromLines();
            $adjustmentDocument->saveQuietly();
        });
    }
}
