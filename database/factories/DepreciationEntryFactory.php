<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\DepreciationEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepreciationEntry>
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
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'journal_entry_id' => null, // FIX: Default to null, as it's created later.
            'status' => 'Posted', // FIX: Align with the status set by the service.
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure(): static
    {
        return $this->afterMaking(function (DepreciationEntry $depreciationEntry) {
            if (! $depreciationEntry->currency_id) {
                // Eager load the relationship to avoid an N+1 problem if creating many
                $asset = $depreciationEntry->asset ?? Asset::find($depreciationEntry->asset_id);
                if ($asset) {
                    $asset->loadMissing('company.currency');
                    $depreciationEntry->currency_id = $asset->company->currency_id;
                }
            }
        });
    }
}
