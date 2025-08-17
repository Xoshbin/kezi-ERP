<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\AdjustmentDocument;
use App\Models\AdjustmentDocumentLine;
use Brick\Money\Money;
use App\Models\Company;
use App\Models\Currency;
use App\Enums\Adjustments\AdjustmentDocumentType;
use App\Enums\Adjustments\AdjustmentDocumentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdjustmentDocument>
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
            'type' => $this->faker->randomElement(AdjustmentDocumentType::cases())->value,
            'date' => $this->faker->date(),
            'reference_number' => $this->faker->unique()->bothify('ADJ-#####'),
            'subtotal' => function (array $attributes) {
                $currency = Currency::find($attributes['currency_id']);
                return Money::of($this->faker->randomFloat(2, 100, 8000), $currency->code);
            },
            'total_tax' => function (array $attributes) {
                $currency = Currency::find($attributes['currency_id']);
                return Money::of($this->faker->randomFloat(2, 0, 2000), $currency->code);
            },
            'total_amount' => function (array $attributes) {
                $currency = Currency::find($attributes['currency_id']);
                $subtotal = $attributes['subtotal'] ?? Money::of(1000, $currency->code);
                $totalTax = $attributes['total_tax'] ?? Money::of(100, $currency->code);
                return $subtotal->plus($totalTax);
            },
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement([AdjustmentDocumentStatus::Draft, AdjustmentDocumentStatus::Posted]),
            'journal_entry_id' => null,
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AdjustmentDocumentStatus::Draft,
            'posted_at' => null,
            'journal_entry_id' => null,
        ]);
    }

    public function posted(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => AdjustmentDocumentStatus::Posted,
                'posted_at' => now(),
                'journal_entry_id' => JournalEntry::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id,
            ];
        });
    }

    public function withLines(int $count = 1): self
    {
        return $this->afterCreating(function (AdjustmentDocument $adjustmentDocument) use ($count) {
            AdjustmentDocumentLine::factory()->count($count)->create([
                'adjustment_document_id' => $adjustmentDocument->id,
            ]);

            // Recalculate totals from lines
            $adjustmentDocument->refresh();
            $adjustmentDocument->calculateTotalsFromLines();
            $adjustmentDocument->saveQuietly();
        });
    }
}
