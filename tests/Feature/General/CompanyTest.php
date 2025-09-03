<?php

namespace Tests\Feature\General;

use App\Models\Currency;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    // This ensures that the specific currencies needed for this test file are
    // always available in the database before any test runs.
    Currency::firstOrCreate(
        ['code' => 'IQD'],
        ['name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'exchange_rate' => 1, 'is_active' => true, 'decimal_places' => 3]
    );
    Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1, 'is_active' => true, 'decimal_places' => 2]
    );
});

test('a company with an IQD base currency can issue an invoice in USD', function () {
    // Arrange: $this->company is our IQD-based company from the WithConfiguredCompany trait.
    // Arrange: Fetch the USD currency, which is now guaranteed to exist.
    $usd = Currency::where('code', 'USD')->firstOrFail();

    // Act: Create an invoice FOR our IQD company, but explicitly
    // override the currency relationship to use USD.
    $usdInvoice = Invoice::factory()
        ->for($this->company)
        ->for($usd, 'currency') // This is the key step
        ->create();

    // Assert: The invoice was created successfully and has the correct currency.
    $this->assertModelExists($usdInvoice);
    expect($usdInvoice->currency_id)->toBe($usd->id);
    expect($usdInvoice->company->currency->code)->toBe('IQD'); // Verify company currency is still IQD
    expect($usdInvoice->currency->code)->toBe('USD'); // Verify invoice currency is USD
});
