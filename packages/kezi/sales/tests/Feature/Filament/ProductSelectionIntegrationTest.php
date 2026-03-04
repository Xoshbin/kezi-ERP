<?php

namespace Kezi\Sales\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages\CreateQuote;
use Kezi\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\CreateSalesOrder;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'USD', 'symbol' => '$', 'decimal_places' => 2]);
    $this->company->update(['currency_id' => $usd->id]);

    $this->customer = Partner::factory()->customer()->create(['company_id' => $this->company->id]);

    Filament::setCurrentPanel(Filament::getPanel('kezi'));
});

it('populates fields when product is selected in Quote', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Quote Product',
        'description' => 'Quote Product',
        'unit_price' => \Brick\Money\Money::of(300, 'USD'),
    ]);

    $this->actingAs($this->user);

    Livewire::test(CreateQuote::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
        ])
        ->set('data.lines.0.product_id', $product->id)
        ->assertSet('data.lines.0.unit_price', '300.00')
        ->assertSet('data.lines.0.description', 'Quote Product');
});

it('populates fields when product is selected in Sales Order', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Sales Order Product',
        'description' => 'Sales Order Product',
        'unit_price' => \Brick\Money\Money::of(400, 'USD'),
    ]);

    $this->actingAs($this->user);

    Livewire::test(CreateSalesOrder::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
        ])
        ->set('data.lines.0.product_id', $product->id)
        ->assertSet('data.lines.0.unit_price', '400.00')
        ->assertSet('data.lines.0.description', 'Sales Order Product');
});
