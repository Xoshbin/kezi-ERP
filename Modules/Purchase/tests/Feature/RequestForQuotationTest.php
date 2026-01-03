<?php

namespace Modules\Purchase\Tests\Feature;

use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Modules\Purchase\Models\RequestForQuotation;
use Modules\Purchase\Services\RequestForQuotationService;

it('creates an RFQ with correct default status', function () {
    $user = \App\Models\User::factory()->create();
    $company = \App\Models\Company::factory()->create();
    $vendor = Partner::factory()->create(['company_id' => $company->id, 'is_vendor' => true]);
    $currency = Currency::factory()->create(['code' => 'USD']);

    $dto = new \Modules\Purchase\DataTransferObjects\Purchases\CreateRFQDTO(
        companyId: $company->id,
        vendorId: $vendor->id,
        currencyId: $currency->id,
        rfqDate: now(),
        createdByUserId: $user->id
    );

    $service = app(RequestForQuotationService::class);
    $rfq = $service->createRFQ($dto);

    expect($rfq)
        ->toBeInstanceOf(RequestForQuotation::class)
        ->status->toBe(RequestForQuotationStatus::Draft)
        ->rfq_number->not->toBeNull();
});

it('can send an RFQ', function () {
    $user = \App\Models\User::factory()->create();
    $company = \App\Models\Company::factory()->create();
    $vendor = Partner::factory()->create(['company_id' => $company->id, 'is_vendor' => true]);
    $currency = Currency::factory()->create(['code' => 'USD']);

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $currency->id,
        'status' => RequestForQuotationStatus::Draft,
    ]);

    $service = app(RequestForQuotationService::class);
    $service->sendRFQ($rfq);

    expect($rfq->fresh()->status)->toBe(RequestForQuotationStatus::Sent);
});

it('can convert RFQ to Purchase Order', function () {
    $user = \App\Models\User::factory()->create();
    $company = \App\Models\Company::factory()->create();
    $vendor = Partner::factory()->create(['company_id' => $company->id, 'is_vendor' => true]);
    $currency = Currency::factory()->create(['code' => 'USD']);

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $currency->id,
        'status' => RequestForQuotationStatus::BidReceived,
    ]);
    /* @var RequestForQuotation $rfq */

    $rfq->lines()->create([
        'description' => 'Test Item',
        'quantity' => 10,
        'unit_price' => 100, // minor units
        'subtotal' => 1000,
        'total' => 1000,
        'tax_amount' => 0,
    ]);

    $dto = new \Modules\Purchase\DataTransferObjects\Purchases\ConvertRFQToPurchaseOrderDTO(
        rfqId: $rfq->id,
        poDate: now()
    );

    $service = app(RequestForQuotationService::class);
    $po = $service->convertToPurchaseOrder($dto);

    expect($po)->toBeInstanceOf(\Modules\Purchase\Models\PurchaseOrder::class);
    expect($rfq->fresh())
        ->status->toBe(RequestForQuotationStatus::Accepted)
        ->converted_to_purchase_order_id->toBe($po->id);

    expect($po->lines)->toHaveCount(1);
    expect($po->lines->first()->description)->toBe('Test Item');
});
