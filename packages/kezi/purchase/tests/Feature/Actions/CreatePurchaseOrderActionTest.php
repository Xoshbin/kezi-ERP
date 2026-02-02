<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\CreatePurchaseOrderAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderLineDTO;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreatePurchaseOrderAction::class);
});

it('creates a purchase order with lines and calculates totals', function () {
    $vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->createSafely(['code' => 'USD']);
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $lineDto = new CreatePurchaseOrderLineDTO(
        product_id: $product->id,
        description: 'Test Product',
        quantity: 10,
        unit_price: Money::of(100, $currency->code),
        tax_id: null
    );

    $dto = new CreatePurchaseOrderDTO(
        company_id: $this->company->id,
        vendor_id: $vendor->id,
        currency_id: $currency->id,
        created_by_user_id: $this->user->id,
        reference: 'PO-001',
        po_date: now(),
        lines: [$lineDto]
    );

    $po = $this->action->execute($dto);

    expect($po)->not->toBeNull();
    expect($po->reference)->toBe('PO-001');
    expect($po->total_amount->getAmount()->toFloat())->toBe(1000.0);
    expect($po->lines)->toHaveCount(1);
});

it('throws PeriodIsLockedException for locked periods', function () {
    // Set a lock date in the future relative to our PO date
    $lockDate = now()->addDay();
    \Kezi\Accounting\Models\LockDate::factory()->create([
        'company_id' => $this->company->id,
        'lock_type' => \Kezi\Accounting\Enums\Accounting\LockDateType::HardLock,
        'locked_until' => $lockDate,
    ]);

    $vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->createSafely(['code' => 'USD']);

    $dto = new CreatePurchaseOrderDTO(
        company_id: $this->company->id,
        vendor_id: $vendor->id,
        currency_id: $currency->id,
        created_by_user_id: $this->user->id,
        po_date: now(), // Today is locked
        lines: []
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(\Kezi\Accounting\Exceptions\PeriodIsLockedException::class);
});
