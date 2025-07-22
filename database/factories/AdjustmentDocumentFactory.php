<?php

namespace Database\Factories;

use App\Models\Company;
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
            'company_id' => Company::factory()->create()->id,
            'original_invoice_id' => null,
            'original_vendor_bill_id' => null,
            'type' => $this->faker->randomElement(['Credit Note', 'Debit Note', 'Miscellaneous Adjustment']),
            'date' => $this->faker->date(),
            'reference_number' => $this->faker->unique()->bothify('ADJ-#####'),
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'total_tax' => $this->faker->randomFloat(2, 0, 2000),
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['Draft', 'Posted']),
            'journal_entry_id' => null,
        ];
    }
}
