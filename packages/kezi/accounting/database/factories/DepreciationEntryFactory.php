<?php

namespace Kezi\Accounting\Database\Factories;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Enums\Assets\DepreciationEntryStatus;
use Kezi\Accounting\Models\Asset;

/**
 * @extends Factory<\Kezi\Accounting\Models\DepreciationEntry>
 */
class DepreciationEntryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Kezi\Accounting\Models\DepreciationEntry>
     */
    protected $model = \Kezi\Accounting\Models\DepreciationEntry::class;

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
