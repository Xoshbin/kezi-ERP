<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Purchase\Actions\Purchases\UpdateRequestForQuotationAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreateRFQLineDTO;
use Modules\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO;
use Modules\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Modules\Purchase\Models\RequestForQuotation;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(UpdateRequestForQuotationAction::class);
});

it('updates a request for quotation and its lines', function () {
    $vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $currency->id,
        'status' => RequestForQuotationStatus::Draft,
    ]);

    // Add initial line using factory to get correct totals
    \Modules\Purchase\Models\RequestForQuotationLine::factory()->create([
        'rfq_id' => $rfq->id,
        'product_id' => $product->id,
        'description' => 'Old RFQ Line',
        'quantity' => 10,
        'unit' => 'pcs',
    ]);

    $lineDto = new CreateRFQLineDTO(
        description: 'Updated RFQ Line',
        quantity: 20,
        product: $product,
        unit: 'pcs',
        unitPrice: Money::of(50, 'USD')
    );

    $dto = new UpdateRFQDTO(
        rfqId: $rfq->id,
        rfq: $rfq,
        vendorId: $vendor->id,
        currencyId: $currency->id,
        rfqDate: $rfq->rfq_date,
        validUntil: now()->addDays(5),
        notes: 'Updated RFQ notes',
        lines: [$lineDto]
    );

    $updatedRfq = $this->action->execute($dto);

    expect($updatedRfq->notes)->toBe('Updated RFQ notes');
    expect($updatedRfq->lines)->toHaveCount(1);
    expect($updatedRfq->lines->first()->description)->toBe('Updated RFQ Line');
    expect((float) $updatedRfq->lines->first()->quantity)->toBe(20.0);
    expect($updatedRfq->total->getAmount()->toFloat())->toBe(1000.0); // 20 * 50

    $this->assertDatabaseHas('request_for_quotations', [
        'id' => $rfq->id,
        'notes' => 'Updated RFQ notes',
    ]);

    $this->assertDatabaseMissing('request_for_quotation_lines', [
        'description' => 'Old RFQ Line',
        'rfq_id' => $rfq->id,
    ]);
});
