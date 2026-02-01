<?php

namespace Kezi\Purchase\Tests\Feature\Filament;

use Livewire\Livewire;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\CreateRequestForQuotation;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\ListRequestForQuotations;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = \Kezi\Foundation\Models\Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    \Filament\Facades\Filament::setCurrentPanel(
        \Filament\Facades\Filament::getPanel('kezi')
    );
    \Filament\Facades\Filament::registerResources([
        \Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource::class,
    ]);
});

it('can render list page', function () {
    $this->actingAs($this->user);

    Livewire::test(ListRequestForQuotations::class)
        ->assertSuccessful();
});

it('can render create page', function () {
    $this->actingAs($this->user);

    Livewire::test(CreateRequestForQuotation::class)
        ->assertSuccessful();
});

it('can create an RFQ', function () {
    $product = \Kezi\Product\Models\Product::factory()->create(['company_id' => $this->company->id]);
    $currency = \Kezi\Foundation\Models\Currency::factory()->create(['code' => 'USD']);

    $this->actingAs($this->user);

    Livewire::test(CreateRequestForQuotation::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'rfq_date' => now(),
            'currency_id' => $currency->id,
            'exchange_rate' => 1,
        ])
        ->set('data.lines', [
            [
                'product_id' => $product->id,
                'description' => 'Test Product',
                'quantity' => 10,
                'unit_price' => 100,
            ],
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('request_for_quotations', [
        'vendor_id' => $this->vendor->id,
        'company_id' => $this->company->id,
    ]);
});
