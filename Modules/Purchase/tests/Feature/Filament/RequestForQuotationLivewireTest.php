<?php

namespace Modules\Purchase\Tests\Feature\Filament;

use Livewire\Livewire;
use Modules\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\CreateRequestForQuotation;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\ViewRequestForQuotation;
use Modules\Purchase\Models\RequestForQuotation;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = \Modules\Foundation\Models\Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    \Filament\Facades\Filament::setCurrentPanel(
        \Filament\Facades\Filament::getPanel('jmeryar')
    );
});

it('validates required fields on create', function () {
    $this->actingAs($this->user);

    Livewire::test(CreateRequestForQuotation::class)
        ->fillForm([
            'company_id' => $this->company->id, // valid
            // vendor_id missing -> null
            'vendor_id' => null,
            'currency_id' => $this->company->currency->id, // valid
        ])
        ->call('create')
        ->assertHasErrors(['data.vendor_id']);
});

it('updates unit price when product is selected', function () {
    // Fix: Match Product currency to Company currency to avoid casting mismatches
    // BaseCurrencyMoneyCast forces company currency on retrieval.
    $currency = $this->company->currency;

    $product = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'average_cost' => \Brick\Money\Money::of(150, $currency->code),
    ]);

    $this->actingAs($this->user);

    Livewire::test(CreateRequestForQuotation::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $currency->id, // Use same currency
        ])
        ->set('data.lines.0.product_id', $product->id)
        ->assertSet('data.lines.0.unit_price', 150.0);
});

it('can send rfq via view page action', function () {
    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'status' => RequestForQuotationStatus::Draft,
        'vendor_id' => $this->vendor->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ViewRequestForQuotation::class, ['record' => $rfq->id])
        ->callAction('send')
        ->assertHasNoErrors();

    expect($rfq->refresh()->status)->toBe(RequestForQuotationStatus::Sent);
});

it('can record bid via view page action', function () {
    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'status' => RequestForQuotationStatus::Sent,
        'vendor_id' => $this->vendor->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ViewRequestForQuotation::class, ['record' => $rfq->id])
        ->callAction('record_bid', [
            'notes' => 'Vendor offered 5% discount.',
        ])
        ->assertHasNoErrors();

    // Status might change or notes updated depending on service logic
    // Checking service logic: RecordVendorBidAction updates notes and sets status to BidReceived?
    // Let's assume so or check side effects.
    $rfq->refresh();
    // Assuming status update happens in Service/Action
    expect($rfq->notes)->toContain('Vendor offered 5% discount.');
});

it('can convert rfq to purchase order via view page action', function () {
    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'status' => RequestForQuotationStatus::BidReceived,
        'vendor_id' => $this->vendor->id,
        'exchange_rate' => 1.0,
    ]);

    // Ensure lines exist for PO creation
    \Modules\Purchase\Models\RequestForQuotationLine::factory()->count(2)->create([
        'rfq_id' => $rfq->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(ViewRequestForQuotation::class, ['record' => $rfq->id])
        ->callAction('convert_to_po', [
            'po_date' => now()->toDateString(),
            'reference' => 'REF-123',
        ])
        ->assertHasNoErrors()
        ->assertNotified()
        ->assertRedirect(); // Redirects to View RFQ Page

    $rfq->refresh();
    expect($rfq->status)->toBe(RequestForQuotationStatus::Accepted)
        ->and($rfq->converted_to_purchase_order_id)->not->toBeNull();

    $this->assertDatabaseHas('purchase_orders', [
        'id' => $rfq->converted_to_purchase_order_id,
        'reference' => 'REF-123',
    ]);
});
