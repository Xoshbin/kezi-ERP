<?php

namespace Modules\Accounting\Database\Factories;

use App\Enums\Assets\DepreciationEntryStatus;
use App\Models\Asset;
use App\Models\DepreciationEntry;
use Brick\Money\Money;
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
        $asset = Asset::factory();

        return [
            'asset_id' => $asset,
            'company_id' => function (array $attributes) {
                return Asset::find($attributes['asset_id'])->company_id;
            },
            'depreciation_date' => $this->faker->date(),
            'amount' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'journal_entry_id' => null, // FIX: Default to null, as it's created later.
            'status' => DepreciationEntryStatus::Posted, // FIX: Align with the status set by the service.
        ];
    }
}
