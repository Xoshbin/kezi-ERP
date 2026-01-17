<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Purchase\Actions\Purchases\ConvertRFQToPurchaseOrderAction;
use Modules\Purchase\DataTransferObjects\Purchases\ConvertRFQToPurchaseOrderDTO;
use Modules\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Modules\Purchase\Events\RequestForQuotationAccepted;
use Modules\Purchase\Models\RequestForQuotation;
use Modules\Purchase\Models\RequestForQuotationLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(ConvertRFQToPurchaseOrderAction::class);
});

it('converts RFQ to Purchase Order when status is BidReceived', function () {
    Event::fake([RequestForQuotationAccepted::class]);

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'status' => RequestForQuotationStatus::BidReceived,
    ]);

    RequestForQuotationLine::factory()->count(2)->create([
        'rfq_id' => $rfq->id,
    ]);

    $dto = new ConvertRFQToPurchaseOrderDTO(
        rfqId: $rfq->id,
        poDate: now(),
        reference: 'PO-REF-001'
    );

    $po = $this->action->execute($dto);

    expect($po)->not->toBeNull();
    expect($po->company_id)->toBe($rfq->company_id);
    expect($po->reference)->toBe('PO-REF-001');
    expect($po->lines)->toHaveCount(2);

    $rfq->refresh();
    expect($rfq->status)->toBe(RequestForQuotationStatus::Accepted);
    expect($rfq->converted_to_purchase_order_id)->toBe($po->id);

    Event::assertDispatched(RequestForQuotationAccepted::class);
});

it('throws exception when RFQ is in wrong status', function () {
    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'status' => RequestForQuotationStatus::Draft,
    ]);

    $dto = new ConvertRFQToPurchaseOrderDTO(
        rfqId: $rfq->id,
        poDate: now()
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(\Exception::class, "RFQ must be in 'Bid Received' or 'Accepted' status to convert.");
});

it('prevents double conversion', function () {
    $po = \Modules\Purchase\Models\PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'status' => RequestForQuotationStatus::BidReceived,
        'converted_to_purchase_order_id' => $po->id,
    ]);

    $dto = new ConvertRFQToPurchaseOrderDTO(
        rfqId: $rfq->id,
        poDate: now()
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(\Exception::class, 'RFQ is already converted to a Purchase Order.');
});
