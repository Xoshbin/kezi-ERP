<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\UpdatePurchaseOrderAction;
use Kezi\Purchase\DataTransferObjects\Purchases\PurchaseOrderLineDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\UpdatePurchaseOrderDTO;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Models\PurchaseOrder;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(UpdatePurchaseOrderAction::class);
});

it('updates a purchase order and replaces lines', function () {
    $vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $currency->id,
        'status' => PurchaseOrderStatus::Draft,
        'po_date' => now()->subDay(),
    ]);

    // Add initial line
    $po->lines()->create([
        'product_id' => $product->id,
        'description' => 'Old Line',
        'quantity' => 1,
        'unit_price' => Money::of(10, 'USD'),
        'subtotal' => Money::of(10, 'USD'),
        'total' => Money::of(10, 'USD'),
    ]);

    $lineDto = new PurchaseOrderLineDTO(
        product_id: $product->id,
        description: 'New Line',
        quantity: 5,
        unit_price: Money::of(100, 'USD')
    );

    $dto = new UpdatePurchaseOrderDTO(
        purchaseOrder: $po,
        vendor_id: $vendor->id,
        currency_id: $currency->id,
        po_date: now()->format('Y-m-d'),
        lines: [$lineDto],
        notes: 'Updated notes',
        reference: 'REF-NEW'
    );

    $updatedPo = $this->action->execute($dto);

    expect($updatedPo->notes)->toBe('Updated notes');
    expect($updatedPo->reference)->toBe('REF-NEW');
    expect($updatedPo->lines)->toHaveCount(1);
    expect($updatedPo->lines->first()->description)->toBe('New Line');
    expect($updatedPo->total_amount->getAmount()->toFloat())->toBe(500.0);

    $this->assertDatabaseHas('purchase_orders', [
        'id' => $po->id,
        'notes' => 'Updated notes',
        'reference' => 'REF-NEW',
    ]);

    $this->assertDatabaseMissing('purchase_order_lines', [
        'description' => 'Old Line',
        'purchase_order_id' => $po->id,
    ]);
});

it('throws exception when purchase order status prevents editing', function () {
    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => PurchaseOrderStatus::Confirmed, // Assuming confirmed cannot be edited
    ]);

    $dto = new UpdatePurchaseOrderDTO(
        purchaseOrder: $po,
        vendor_id: $po->vendor_id,
        currency_id: $po->currency_id,
        po_date: now()->format('Y-m-d'),
        lines: []
    );

    $this->action->execute($dto);
})->throws(\Kezi\Foundation\Exceptions\UpdateNotAllowedException::class);

it('enforces accounting lock date', function () {
    // Create HardLock lock date until tomorrow
    \Kezi\Accounting\Models\LockDate::create([
        'company_id' => $this->company->id,
        'lock_type' => \Kezi\Accounting\Enums\Accounting\LockDateType::HardLock->value,
        'locked_until' => now()->addDay(),
    ]);

    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    $dto = new UpdatePurchaseOrderDTO(
        purchaseOrder: $po,
        vendor_id: $po->vendor_id,
        currency_id: $po->currency_id,
        po_date: now()->format('Y-m-d'),
        lines: []
    );

    $this->action->execute($dto);
})->throws(\Kezi\Accounting\Exceptions\PeriodIsLockedException::class);
