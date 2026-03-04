<?php

namespace Kezi\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\CreateInvoice;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'USD', 'symbol' => '$', 'decimal_places' => 2]);
    $this->company->update(['currency_id' => $usd->id]);

    $this->partner = Partner::factory()->create(['company_id' => $this->company->id]);

    Filament::setCurrentPanel(Filament::getPanel('kezi'));
});

it('populates fields when product is selected in Invoice', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Invoice Product',
        'description' => 'Invoice Description',
        'unit_price' => \Brick\Money\Money::of(500, 'USD'),
    ]);

    $this->actingAs($this->user);

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
        ])
        ->set('data.invoiceLines.0.product_id', $product->id)
        ->assertSet('data.invoiceLines.0.unit_price', '500.00')
        ->assertSet('data.invoiceLines.0.description', 'Invoice Description');
});

it('populates fields when product is selected in Vendor Bill', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Vendor Bill Product',
        'description' => null,
        'unit_price' => \Brick\Money\Money::of(600, 'USD'),
    ]);

    $this->actingAs($this->user);

    Livewire::test(CreateVendorBill::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
        ])
        ->set('data.lines.0.product_id', $product->id)
        ->assertSet('data.lines.0.unit_price', '600.00')
        ->assertSet('data.lines.0.description', 'Vendor Bill Product');
});
