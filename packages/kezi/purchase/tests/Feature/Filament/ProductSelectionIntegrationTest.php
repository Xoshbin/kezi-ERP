<?php

namespace Kezi\Purchase\Tests\Feature\Filament;

use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\CreateRequestForQuotation;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'USD', 'symbol' => '$', 'decimal_places' => 2]);
    $this->company->update(['currency_id' => $usd->id]);

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('kezi'));
});

it('populates fields when product is selected in RFQ', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Purchase Product',
        'description' => 'Purchase Product',
        'average_cost' => \Brick\Money\Money::of(150, 'USD'),
    ]);

    $this->actingAs($this->user);

    Livewire::test(CreateRequestForQuotation::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
        ])
        ->set('data.lines.0.product_id', $product->id)
        ->assertSet('data.lines.0.unit_price', '150.00')
        ->assertSet('data.lines.0.description', 'Purchase Product');
});

it('populates fields when product is selected in Purchase Order', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'PO Product',
        'description' => 'PO Product',
        'unit_price' => \Brick\Money\Money::of(200, 'USD'),
    ]);

    $this->actingAs($this->user);

    Livewire::test(CreatePurchaseOrder::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
        ])
        ->set('data.lines.0.product_id', $product->id)
        ->assertSet('data.lines.0.unit_price', '200')
        ->assertSet('data.lines.0.description', 'PO Product');
});
