<?php

namespace Kezi\Purchase\Tests\Feature\Filament;

use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\CreateRequestForQuotation;
use Kezi\Purchase\Models\RequestForQuotation;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Ensure we have USD and IQD currencies available
    $this->usd = Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]
    );

    $this->iqd = Currency::firstOrCreate(
        ['code' => 'IQD'],
        ['name' => 'Iraqi Dinar', 'symbol' => 'د.ع', 'decimal_places' => 0, 'is_active' => true]
    );

    // Set up vendor
    $this->vendor = \Kezi\Foundation\Models\Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    \Filament\Facades\Filament::setCurrentPanel(
        \Filament\Facades\Filament::getPanel('kezi')
    );
});

it('can create RFQ using the company default currency', function () {
    // Set company base currency to USD
    $this->company->update(['currency_id' => $this->usd->id]);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'average_cost' => \Brick\Money\Money::of(100, 'USD'),
    ]);

    $this->actingAs($this->user);

    Livewire::test(CreateRequestForQuotation::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->usd->id,
            'rfq_date' => now()->format('Y-m-d'),
        ])
        ->set('data.lines', [
            'item1' => [
                'product_id' => $product->id,
                'description' => 'Test Product',
                'quantity' => 2,
                'unit_price' => 100,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $rfq = RequestForQuotation::latest()->first();
    expect($rfq->currency_id)->toBe($this->usd->id);
    expect($rfq->total->getAmount()->toFloat())->toBe(200.0);
    expect($rfq->total->getCurrency()->getCurrencyCode())->toBe('USD');
});

it('can create RFQ using a currency different from the company default currency', function () {
    // Set company base currency to USD
    $this->company->update(['currency_id' => $this->usd->id]);

    // Set exchange rate for IQD: 1 USD = 1300 IQD
    CurrencyRate::create([
        'currency_id' => $this->iqd->id,
        'company_id' => $this->company->id,
        'rate' => 1300,
        'effective_date' => now(),
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'average_cost' => \Brick\Money\Money::of(130000, 'IQD'),
    ]);

    $this->actingAs($this->user);

    Livewire::test(CreateRequestForQuotation::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->iqd->id,
            'exchange_rate' => 1300,
            'rfq_date' => now()->format('Y-m-d'),
        ])
        ->set('data.lines', [
            'item1' => [
                'product_id' => $product->id,
                'description' => 'Test Product IQD',
                'quantity' => 1,
                'unit_price' => 130000,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $rfq = RequestForQuotation::latest()->first();
    expect($rfq->currency_id)->toBe($this->iqd->id);
    expect($rfq->total->getAmount()->toFloat())->toBe(130000.0);
    expect($rfq->total->getCurrency()->getCurrencyCode())->toBe('IQD');
});
