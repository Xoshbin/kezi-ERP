<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Partner;
use Kezi\Purchase\Actions\Purchases\CancelRequestForQuotationAction;
use Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Kezi\Purchase\Models\RequestForQuotation;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CancelRequestForQuotationAction::class);
});

it('cancels a request for quotation', function () {
    $vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'status' => RequestForQuotationStatus::Draft,
    ]);

    $cancelledRfq = $this->action->execute($rfq);

    expect($cancelledRfq->status)->toBe(RequestForQuotationStatus::Cancelled);
    $this->assertDatabaseHas('request_for_quotations', [
        'id' => $rfq->id,
        'status' => RequestForQuotationStatus::Cancelled->value,
    ]);
});
