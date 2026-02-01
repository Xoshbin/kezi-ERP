<?php

namespace Jmeryar\Purchase\Tests\Feature;

use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Jmeryar\Purchase\Models\RequestForQuotation;
use Jmeryar\Purchase\Services\RequestForQuotationService;

it('creates an RFQ with correct default status', function () {
    $user = \App\Models\User::factory()->create();
    $company = \App\Models\Company::factory()->create();
    $vendor = Partner::factory()->vendor()->create(['company_id' => $company->id]);
    $currency = Currency::factory()->create(['code' => 'USD']);

    $dto = new \Jmeryar\Purchase\DataTransferObjects\Purchases\CreateRFQDTO(
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
    $vendor = Partner::factory()->vendor()->create(['company_id' => $company->id]);
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
    $this->actingAs($user);
    $company = \App\Models\Company::factory()->create();
    $vendor = Partner::factory()->vendor()->create(['company_id' => $company->id]);
    $currency = Currency::factory()->create(['code' => 'USD']);

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $currency->id,
        'created_by_user_id' => $user->id,
        'status' => RequestForQuotationStatus::BidReceived,
    ]);
    /* @var RequestForQuotation $rfq */

    $line = new \Jmeryar\Purchase\Models\RequestForQuotationLine;
    $line->rfq()->associate($rfq);
    $product = \Jmeryar\Product\Models\Product::factory()->create();
    $line->fill([
        'product_id' => $product->id,
        'description' => 'Test Item',
        'quantity' => 10,
        'unit_price' => \Brick\Money\Money::of(100, $currency->code),
        'subtotal' => \Brick\Money\Money::of(1000, $currency->code),
        'total' => \Brick\Money\Money::of(1000, $currency->code),
        'tax_amount' => \Brick\Money\Money::of(0, $currency->code),
    ]);
    $line->save();

    $dto = new \Jmeryar\Purchase\DataTransferObjects\Purchases\ConvertRFQToPurchaseOrderDTO(
        rfqId: $rfq->id,
        poDate: now()
    );

    $service = app(RequestForQuotationService::class);
    $po = $service->convertToPurchaseOrder($dto);

    expect($po)->toBeInstanceOf(\Jmeryar\Purchase\Models\PurchaseOrder::class);
    expect($rfq->fresh())
        ->status->toBe(RequestForQuotationStatus::Accepted)
        ->converted_to_purchase_order_id->toBe($po->id);

    expect($po->lines)->toHaveCount(1);
    expect($po->lines->first()->description)->toBe('Test Item');
});
