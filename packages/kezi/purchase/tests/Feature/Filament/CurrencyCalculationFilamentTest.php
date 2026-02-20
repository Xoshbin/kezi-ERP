<?php

use Brick\Money\Money;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Kezi\Foundation\Models\Partner;
use Kezi\Purchase\Models\VendorBill;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);

    // Setup currencies
    $this->iqd = Currency::firstOrCreate(
        ['code' => 'IQD'],
        ['name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'decimal_places' => 3, 'symbol_position' => 'after']
    );
    $this->company->update(['currency_id' => $this->iqd->id]);

    $this->usd = Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'symbol_position' => 'before']
    );

    // Setup exchange rate: 1 USD = 1250 IQD
    CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->usd->id,
        'rate' => 1250.0,
        'effective_date' => now()->subDay(),
    ]);

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
});

test('vendor bill filament form correctly calculates and displays company currency totals', function () {
    $product = \Kezi\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'unit_price' => Money::of(100.25, $this->usd->code),
        'expense_account_id' => \Kezi\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'expense'])->id,
    ]);

    // 1. Create a Vendor Bill via Livewire
    $livewire = Livewire::test(CreateVendorBill::class, ['tenant' => $this->company])
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->usd->id,
            'exchange_rate_at_creation' => 1250.0,
            'bill_reference' => 'BILL-USD-001',
            'bill_date' => now()->toDateString(),
            'accounting_date' => now()->toDateString(),
        ])
        ->set('data.lines', [
            [
                'product_id' => $product->id,
                'description' => 'Test Item',
                'quantity' => 10,
                'unit_price' => 100.25, // $1002.50 USD total
                'expense_account_id' => $product->expense_account_id,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $vendorBill = VendorBill::where('bill_reference', 'BILL-USD-001')->first();
    expect($vendorBill)->not->toBeNull();

    // 2. Open Edit page to verify company currency totals are visible and correct
    livewire(EditVendorBill::class, [
        'record' => $vendorBill->getRouteKey(),
        'tenant' => $this->company,
    ])
        ->assertFormSet([
            'total_amount_company_currency' => 1253125.0, // MoneyInput handles the numeric value
            'total_tax_company_currency' => 0.0,
        ]);
});
