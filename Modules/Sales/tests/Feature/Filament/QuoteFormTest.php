<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages\CreateQuote;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('updates unit price and description when product is selected', function () {
    $product = Product::factory()->for($this->company)->create([
        'name' => 'Test Product',
        'unit_price' => Money::of(500, 'IQD'),
    ]);

    // Create a generic partner
    $partner = Partner::factory()->for($this->company)->create();

    Livewire::test(CreateQuote::class)
        ->fillForm([
            'partner_id' => $partner->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->set('data.lines', [
            [
                'product_id' => $product->id,
            ],
        ])
        // We trigger the update by re-setting the specific field if needed, or relying on fillForm
        // In Livewire tests, set() triggers updates.
        ->set('data.lines.0.product_id', $product->id)
        // Using dot notation for specific fields is safer and avoids issues with extra keys/UUIDs
        ->assertFormSet([
            'lines.0.description' => 'Test Product',
            'lines.0.unit_price' => '500.000', // IQD has 3 decimals
        ]);
});

it('updates section total when quantity changes', function () {
    $partner = Partner::factory()->for($this->company)->create();

    Livewire::test(CreateQuote::class)
        ->fillForm([
            'partner_id' => $partner->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->set('data.lines', [
            [
                'quantity' => 1,
                'unit_price' => 100,
                'discount_percentage' => 0,
            ],
        ])
        ->assertFormSet(['subtotal' => 100])
        ->set('data.lines.0.quantity', 2)
        ->assertFormSet(['subtotal' => 200]);
});

it('updates section total when discount changes', function () {
    $partner = Partner::factory()->for($this->company)->create();

    Livewire::test(CreateQuote::class)
        ->fillForm([
            'partner_id' => $partner->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->set('data.lines', [
            [
                'quantity' => 1,
                'unit_price' => 100,
                'discount_percentage' => 0,
            ],
        ])
        ->assertFormSet(['subtotal' => 100])
        ->set('data.lines.0.discount_percentage', 10)
        ->assertFormSet(['subtotal' => 90]);
});

it('updates grand totals when line added/removed', function () {
    $partner = Partner::factory()->for($this->company)->create();

    $component = Livewire::test(CreateQuote::class)
        ->fillForm([
            'partner_id' => $partner->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->set('data.lines', [
            [
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ])
        ->assertFormSet(['total' => 100]);

    // Add another line
    $component->set('data.lines', [
        [
            'quantity' => 1,
            'unit_price' => 100,
        ],
        [
            'quantity' => 1,
            'unit_price' => 50,
        ],
    ])
        ->assertFormSet(['total' => 150]);
});

it('shows exchange rate field for foreign currency', function () {
    $partner = Partner::factory()->for($this->company)->create();

    // Create USD currency
    $usd = Currency::factory()->create(['code' => 'USD', 'decimal_places' => 2]);

    Livewire::test(CreateQuote::class)
        ->fillForm([
            'partner_id' => $partner->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->assertFormSet(['exchange_rate' => 1])
        ->assertFormFieldIsHidden('exchange_rate')

        ->fillForm(['currency_id' => $usd->id])
        ->assertFormFieldIsVisible('exchange_rate')
        ->assertFormSet(['exchange_rate' => 1]);
});
