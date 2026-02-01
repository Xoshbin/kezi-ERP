<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Purchase\Actions\Purchases\CancelRequestForQuotationAction;
use Jmeryar\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Jmeryar\Purchase\Models\RequestForQuotation;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CancelRequestForQuotationAction::class);
});

it('cancels a request for quotation', function () {
    $vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Foundation\Enums\Partners\PartnerType::Vendor,
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
