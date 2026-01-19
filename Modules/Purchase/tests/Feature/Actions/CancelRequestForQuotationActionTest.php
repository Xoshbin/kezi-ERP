<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Partner;
use Modules\Purchase\Actions\Purchases\CancelRequestForQuotationAction;
use Modules\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Modules\Purchase\Models\RequestForQuotation;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CancelRequestForQuotationAction::class);
});

it('cancels a request for quotation', function () {
    $vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Vendor,
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
