<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\CreateRequestForQuotationAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateRFQDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateRFQLineDTO;
use Kezi\Purchase\Events\RequestForQuotationCreated;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateRequestForQuotationAction::class);
});

it('creates a request for quotation with lines and calculates totals', function () {
    Event::fake([RequestForQuotationCreated::class]);

    $vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->createSafely(['code' => 'USD']);
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $lineDto1 = new CreateRFQLineDTO(
        description: 'Test Product 1',
        quantity: 10,
        product: $product,
        unit: 'pcs',
        unitPrice: Money::of(100, $currency->code)
    );

    $lineDto2 = new CreateRFQLineDTO(
        description: 'Test Product 2',
        quantity: 5,
        product: $product,
        unit: 'pcs',
        unitPrice: Money::of(50, $currency->code)
    );

    $dto = new CreateRFQDTO(
        companyId: $this->company->id,
        vendorId: $vendor->id,
        currencyId: $currency->id,
        rfqDate: now(),
        validUntil: now()->addDays(7),
        notes: 'Test RFQ notes',
        exchangeRate: 1.0,
        createdByUserId: $this->user->id,
        lines: [$lineDto1, $lineDto2]
    );

    $rfq = $this->action->execute($dto);

    expect($rfq)->not->toBeNull();
    expect($rfq->rfq_number)->not->toBeNull(); // Sequence assigned
    expect($rfq->total->getAmount()->toFloat())->toBe(1250.0); // (10 * 100) + (5 * 50)
    expect($rfq->lines)->toHaveCount(2);

    Event::assertDispatched(RequestForQuotationCreated::class, function ($event) use ($rfq) {
        return $event->rfq->id === $rfq->id;
    });
});
