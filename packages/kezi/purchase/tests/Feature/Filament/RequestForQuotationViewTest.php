<?php

namespace Kezi\Purchase\Tests\Feature\Filament;

use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\ViewRequestForQuotation;
use Kezi\Purchase\Models\RequestForQuotation;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    \Filament\Facades\Filament::setCurrentPanel(
        \Filament\Facades\Filament::getPanel('kezi')
    );
    \Filament\Facades\Filament::registerResources([
        \Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource::class,
    ]);
});

it('displays company currency totals on view page when currency differs', function () {
    // Create a foreign currency
    $usd = Currency::factory()->createSafely(['code' => 'USD', 'symbol' => '$', 'decimal_places' => 2]);

    // Ensure company has a different currency (default in tests is usually IQD or similar)
    $this->company->currency_id = Currency::factory()->createSafely(['code' => 'IQD', 'symbol' => 'ID', 'decimal_places' => 3])->id;
    $this->company->save();

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $usd->id,
        'exchange_rate' => 1460.00,
        'subtotal' => 1000, // 1000 USD
        'tax_total' => 100,  // 100 USD
        'total' => 1100,    // 1100 USD
    ]);

    $this->actingAs($this->user);

    // Verify calculated attributes
    expect($rfq->subtotal_company_currency->getAmount()->toFloat())->toBe(1460000.0);
    expect($rfq->tax_total_company_currency->getAmount()->toFloat())->toBe(146000.0);
    expect($rfq->total_company_currency->getAmount()->toFloat())->toBe(1606000.0);

    $res = Livewire::test(ViewRequestForQuotation::class, [
        'record' => $rfq->id,
    ]);

    // Verify in HTML (ignoring formatting like commas)
    $res->assertSuccessful();
    $html = $res->html();
    $cleanHtml = str_replace(',', '', $html);

    expect($cleanHtml)->toContain('1460000.000')
        ->toContain('146000.000')
        ->toContain('1606000.000');
});
