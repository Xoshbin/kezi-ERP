<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Purchase\Actions\Purchases\RecordVendorBidAction;
use Kezi\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO;
use Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Kezi\Purchase\Models\RequestForQuotation;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(RecordVendorBidAction::class);
});

// Let's check the action name again
it('records a vendor bid', function () {
    $this->action = app(RecordVendorBidAction::class); // Corrected spelling

    $vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $currency->id,
        'status' => RequestForQuotationStatus::Sent,
    ]);

    $dto = new UpdateRFQDTO(
        rfqId: $rfq->id,
        rfq: $rfq,
        vendorId: $vendor->id,
        currencyId: $currency->id,
        notes: 'Updated notes from vendor',
        rfqDate: $rfq->rfq_date,
        validUntil: now()->addDays(10),
        lines: [] // Empty lines as simple update for status/notes
    );

    $updatedRfq = $this->action->execute($rfq, $dto);

    expect($updatedRfq->status)->toBe(RequestForQuotationStatus::BidReceived);
    expect($updatedRfq->notes)->toBe('Updated notes from vendor');
    expect($updatedRfq->valid_until->format('Y-m-d'))->toBe(now()->addDays(10)->format('Y-m-d'));

    $this->assertDatabaseHas('request_for_quotations', [
        'id' => $rfq->id,
        'status' => RequestForQuotationStatus::BidReceived->value,
        'notes' => 'Updated notes from vendor',
    ]);
});
