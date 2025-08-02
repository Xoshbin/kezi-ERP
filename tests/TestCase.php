<?php

namespace Tests;

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\CreatesApplication;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies
        Currency::firstOrCreate(['code' => 'IQD'], ['name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'exchange_rate' => 1, 'is_active' => true, 'decimal_places' => 3]);
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1, 'is_active' => true, 'decimal_places' => 2]);
    }
}
