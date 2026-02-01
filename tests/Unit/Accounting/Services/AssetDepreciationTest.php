<?php

namespace Tests\Unit\Accounting\Services;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;
use Kezi\Accounting\Models\Asset;
use Kezi\Accounting\Services\AssetService;
use Kezi\Foundation\Models\Currency;
use Tests\TestCase;

class AssetDepreciationTest extends TestCase
{
    use RefreshDatabase;

    protected AssetService $service;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AssetService::class);
        $this->currency = Currency::factory()->createSafely(['code' => 'USD']);
    }

    public function test_straight_line_standard()
    {
        $asset = Asset::factory()->create([
            'purchase_value' => Money::of(12000, 'USD'),
            'salvage_value' => Money::of(0, 'USD'),
            'useful_life_years' => 1,
            'purchase_date' => '2024-01-01',
            'depreciation_method' => DepreciationMethod::StraightLine,
            'prorata_temporis' => false,
            'currency_id' => $this->currency->id,
        ]);

        $entries = $this->service->computeDepreciation($asset);

        // 1 year = 12 months. 12000 / 12 = 1000 per month.
        $this->assertCount(12, $entries);

        $firstEntry = $entries->first();
        $this->assertTrue($firstEntry->amount->isEqualTo(Money::of(1000, 'USD')));
        $this->assertEquals('2024-01-31', $firstEntry->depreciation_date->format('Y-m-d'));

        $lastEntry = $entries->last();
        $this->assertTrue($lastEntry->amount->isEqualTo(Money::of(1000, 'USD')));
        $this->assertEquals('2024-12-31', $lastEntry->depreciation_date->format('Y-m-d'));
    }

    public function test_straight_line_prorata()
    {
        // Purchased in middle of Jan (15th). Jan has 31 days.
        // Active days in Jan: 31 - 15 + 1 = 17 days.
        $asset = Asset::factory()->create([
            'purchase_value' => Money::of(12000, 'USD'),
            'salvage_value' => Money::of(0, 'USD'),
            'useful_life_years' => 1,
            'purchase_date' => '2024-01-15',
            'depreciation_method' => DepreciationMethod::StraightLine,
            'prorata_temporis' => true,
            'currency_id' => $this->currency->id,
        ]);

        $entries = $this->service->computeDepreciation($asset);

        // Should have 13 entries (1 partial start, 11 full, 1 partial end)
        $this->assertCount(13, $entries);

        // Standard monthly = 1000.
        // Jan: 1000 * 17 / 31 = 548.39 (rounded half up -> 548 or depending on precision).
        // Let's check logic: 1000 * 17 / 31 = 548.387... -> 548 using HALF_UP if no decimals?
        // Brick Money default scale might be 2? Code says BaseCurrencyMoneyCast uses integer storage but Money object has context.
        // Assuming USD has scale 2 (cents).
        // 1000.00 * 17 / 31 = 548.387... -> 548.39

        $firstEntry = $entries->shift();
        // 1000 * 17 / 31 = 548.39
        $this->assertEquals(548.39, $firstEntry->amount->getAmount()->toFloat());
        $this->assertEquals('2024-01-31', $firstEntry->depreciation_date->format('Y-m-d'));

        // Middle 11 entries should be 1000
        for ($i = 0; $i < 11; $i++) {
            $entry = $entries->shift();
            $this->assertEquals(1000.00, $entry->amount->getAmount()->toFloat());
        }

        // Last entry should be remainder
        // Total Depreciated so far = 548.39 + 11000 = 11548.39
        // Remaining = 12000 - 11548.39 = 451.61
        $lastEntry = $entries->shift();
        $this->assertEquals(451.61, $lastEntry->amount->getAmount()->toFloat());
        $this->assertEquals('2025-01-31', $lastEntry->depreciation_date->format('Y-m-d')); // Or end of that month? Code logic uses copy() of date.
        // Code check logic: $currentDate is incremented.
    }

    public function test_sum_of_digits()
    {
        // Cost 12000, Life 3 Years.
        // SYD = 3(4)/2 = 6.
        // Year 1: 12000 * 3/6 = 6000. Monthly = 500.
        // Year 2: 12000 * 2/6 = 4000. Monthly = 333.33...
        // Year 3: 12000 * 1/6 = 2000. Monthly = 166.66...

        $asset = Asset::factory()->create([
            'purchase_value' => Money::of(12000, 'USD'),
            'salvage_value' => Money::of(0, 'USD'),
            'useful_life_years' => 3,
            'purchase_date' => '2024-01-01',
            'depreciation_method' => DepreciationMethod::SumOfDigits,
            'prorata_temporis' => false,
            'currency_id' => $this->currency->id,
        ]);

        $entries = $this->service->computeDepreciation($asset);
        $this->assertCount(36, $entries); // 3 * 12

        // First 12 months should be (6000 / 12) = 500
        for ($i = 0; $i < 12; $i++) {
            $this->assertEquals(500.00, $entries[$i]->amount->getAmount()->toFloat());
        }

        // Next 12 months: 4000/12 = 333.33
        for ($i = 12; $i < 24; $i++) {
            $this->assertEquals(333.33, $entries[$i]->amount->getAmount()->toFloat());
        }

        // Next 12 months: 2000/12 = 166.67
        for ($i = 24; $i < 36; $i++) {
            $this->assertEquals(166.67, $entries[$i]->amount->getAmount()->toFloat());
        }
    }

    public function test_declining_balance_double()
    {
        // Cost 10000, Life 5 Years.
        // Straight Line Rate = 20%. Double Declining Rate = 40%.
        // Year 1: 10000 * 40% = 4000. Monthly = 333.33

        $asset = Asset::factory()->create([
            'purchase_value' => Money::of(10000, 'USD'),
            'salvage_value' => Money::of(0, 'USD'),
            'useful_life_years' => 5,
            'purchase_date' => '2024-01-01',
            'depreciation_method' => DepreciationMethod::Declining,
            'declining_factor' => 2.0,
            'currency_id' => $this->currency->id,
        ]);

        $entries = $this->service->computeDepreciation($asset);

        $this->assertCount(60, $entries);

        $total = \Brick\Money\Money::zero('USD');
        foreach ($entries as $entry) {
            $total = $total->plus($entry->amount);
        }

        // Total should be close to 10000
        $this->assertEquals(10000.00, $total->getAmount()->toFloat());

        // First month check
        // 10000 * 2 / (5*12) = 10000 * 2 / 60 = 333.33
        $this->assertEquals(333.33, $entries->first()->amount->getAmount()->toFloat());
    }
}
