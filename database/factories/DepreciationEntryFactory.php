<?php

namespace Database\Factories;

use App\Models\Asset;
use Brick\Money\Money;
use App\Models\Currency;
use App\Models\DepreciationEntry;
use App\Enums\Assets\DepreciationEntryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepreciationEntry>
 */
class DepreciationEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(), // Better default than a random number
            'depreciation_date' => $this->faker->date(),
            'amount' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'journal_entry_id' => null, // FIX: Default to null, as it's created later.
            'status' => DepreciationEntryStatus::Posted, // FIX: Align with the status set by the service.
        ];
    }

}
