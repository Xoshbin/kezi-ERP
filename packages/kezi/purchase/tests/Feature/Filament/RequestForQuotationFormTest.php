<?php

namespace Kezi\Purchase\Tests\Feature\Filament;

use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\CreateRequestForQuotation;
use Livewire\Livewire;
use Kezi\Product\Models\Product;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Ensure we have USD and EUR currencies available
    $this->usd = Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]
    );

    $this->eur = Currency::firstOrCreate(
        ['code' => 'EUR'],
        ['name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'is_active' => true]
    );

    // Set up vendor
    $this->vendor = \Kezi\Foundation\Models\Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    \Filament\Facades\Filament::setCurrentPanel(
        \Filament\Facades\Filament::getPanel('kezi')
    );
});

it('defaults to company currency on create', function () {
    // Set company base currency to EUR (different from the hardcoded USD default)
    $this->company->update(['currency_id' => $this->eur->id]);

    $this->actingAs($this->user);

    Livewire::test(CreateRequestForQuotation::class)
        ->assertFormSet([
            'currency_id' => $this->eur->id,
        ]);
});

it('updates exchange rate when currency changes', function () {
    // Set company base currency to USD
    $this->company->update(['currency_id' => $this->usd->id]);

    // Create exchange rate: 1 EUR = 1.1 USD
    CurrencyRate::create([
        'currency_id' => $this->eur->id,
        'company_id' => $this->company->id,
        'rate' => 1.1,
        'effective_date' => now(),
    ]);

    $this->actingAs($this->user);

    Livewire::test(CreateRequestForQuotation::class)
        ->assertFormSet(['currency_id' => $this->usd->id]) // Should default to USD (company currency)
        ->fillForm([
            'currency_id' => $this->eur->id,
        ])
        ->assertFormSet([
            'exchange_rate' => 1.1,
        ]);
});

it('recalculates line item prices when currency changes', function () {
    // Set company base currency to USD
    $this->company->update(['currency_id' => $this->usd->id]);

    // Create exchange rate: 1 EUR = 1.1 USD
    CurrencyRate::create([
        'currency_id' => $this->eur->id,
        'company_id' => $this->company->id,
        'rate' => 1.1,
        'effective_date' => now(),
    ]);

    // Create product with price 110 USD
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'average_cost' => \Brick\Money\Money::of(110, 'USD'),
        'unit_price' => \Brick\Money\Money::of(110, 'USD'),
    ]);

    $this->actingAs($this->user);

    Livewire::test(CreateRequestForQuotation::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->usd->id,
        ])
        // Use implicit repeater initialization
        ->set('data.lines.0.product_id', $product->id)
        ->assertSet('data.lines.0.unit_price', 110.00) // 110 USD

        // Change currency to EUR
        ->set('data.currency_id', $this->eur->id)

        // Assert price converted: 110 USD / 1.1 = 100 EUR
        // Note: assertions on numbers might need strictness check.
        // If the implementation uses strings for Money inputs, we expect '100.000000' or similar
        // depending on Brick/Math scale.
        ->assertSet('data.lines.0.unit_price', '100.000000');
});
