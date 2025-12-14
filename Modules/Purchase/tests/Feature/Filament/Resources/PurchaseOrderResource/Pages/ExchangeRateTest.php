<?php

namespace Modules\Purchase\tests\Feature\Filament\Resources\PurchaseOrderResource\Pages;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    Filament::setTenant($this->company);

    // Setup currencies
    $this->iqd = $this->company->currency; // Base currency
    $this->usd = Currency::factory()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'decimal_places' => 2,
    ]);

    // Setup exchange rate: 1 USD = 1500 IQD
    // But verify how CurrencyConverterService uses it.
    // If base is IQD, and we want to convert TO USD.
    // The service `convertFromBaseCurrency` divides by rate.
    // So if 1 USD = 1500 IQD, the rate stored should be 1500?
    // Let's verify with the service test I'm about to read, but standard is usually Base/Foreign or Foreign/Base.

    // Assuming Rate is "How many Base Units for 1 Foreign Unit".
    // Logic in Service: $amount->dividedBy($rate)
    // If 150000 IQD -> ? USD. 150000 / 1500 = 100 USD. Correct.

    CurrencyRate::create([
        'company_id' => $this->company->id,
        'currency_id' => $this->usd->id,
        'rate' => 1500.00, // 1 USD = 1500 IQD
        'date' => now()->startOfDay(),
    ]);
});

test('exchange rate field initially hidden or shows 1 for base currency', function () {
    Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->assertFormSet([
            'exchange_rate_at_creation' => 1,
        ]);
});

test('exchange rate field populates when foreign currency selected', function () {
    Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'currency_id' => $this->usd->id,
        ])
        ->assertFormSet([
            'exchange_rate_at_creation' => 1500.00,
        ]);
});

test('product price is converted using system exchange rate', function () {
    // Product Price: 1,500,000 IQD
    // Rate: 1500 IQD/USD
    // Expected: 1,000 USD
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'unit_price' => 1500000,
    ]);

    Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->usd->id,
        ])
        ->set('data.lines', [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ])
        ->assertFormSet([
            'lines.0.unit_price' => '1000.00',
        ]);
});

test('product price is converted using COMPLETED manual exchange rate', function () {
    // Product Price: 1,500,000 IQD
    // Manual Rate: 1000 IQD/USD (Stronger Dinar / Weaker Dollar example)
    // Expected: 1,500 USD
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'unit_price' => 1500000,
    ]);

    Livewire::test(CreatePurchaseOrder::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->usd->id,
            'exchange_rate_at_creation' => 1000,
        ])
        // Simulate reacting to exchange rate change if we implement that,
        // OR just proceed to select product after setting rate.
        ->set('data.lines', [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ])
        ->assertFormSet([
            'lines.0.unit_price' => '1500.00',
        ]);
});
