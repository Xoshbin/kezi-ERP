<?php

namespace Kezi\Purchase\tests\Feature\Filament\Resources\PurchaseOrderResource\Pages;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    Filament::setTenant($this->company);

    // Setup currencies
    $this->iqd = $this->company->currency; // Base currency
    $this->usd = Currency::factory()->createSafely([
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
        'effective_date' => now()->startOfDay(),
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
        ->assertFormSet([
            'exchange_rate_at_creation' => 1500.00,
        ])
        // We need to set the product_id specifically to trigger the afterStateUpdated hook
        // Since minItems(1) is set, we can assume the first item exists or we add one.
        // However, Filament tests might need us to initialize the repeater structure if it's not auto-filled in test env.
        // Let's try setting the array first to ensure structure, then updating the specific field to trigger the hook.
        ->set('data.lines', [
            [
                'product_id' => null, // Start empty
                'quantity' => 1,
            ],
        ])
        ->set('data.lines.0.product_id', $product->id)
        ->assertFormSet([
            'lines.0.unit_price' => '1000',
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
        ->set('data.lines', [
            [
                'product_id' => null,
                'quantity' => 1,
            ],
        ])
        ->set('data.lines.0.product_id', $product->id)
        ->assertFormSet([
            'lines.0.unit_price' => '1500',
        ]);
});
