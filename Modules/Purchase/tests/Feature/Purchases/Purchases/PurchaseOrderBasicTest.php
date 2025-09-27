<?php

namespace Tests\Feature\Purchases;

use App\Actions\Purchases\CreatePurchaseOrderAction;
use App\DataTransferObjects\Purchases\CreatePurchaseOrderDTO;
use App\DataTransferObjects\Purchases\CreatePurchaseOrderLineDTO;
use App\Enums\Products\ProductType;
use App\Enums\Purchases\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
    ]);
});

it('can create a basic purchase order', function () {
    $lineDto = new CreatePurchaseOrderLineDTO(
        product_id: $this->product->id,
        description: 'Test Product Purchase',
        quantity: 10.0,
        unit_price: Money::of(1000, $this->company->currency->code),
        tax_id: null,
    );

    $dto = new CreatePurchaseOrderDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        currency_id: $this->company->currency->id,
        created_by_user_id: $this->user->id,
        reference: 'TEST-REF-001',
        po_date: now(),
        lines: [$lineDto],
    );

    $purchaseOrder = app(CreatePurchaseOrderAction::class)->execute($dto);

    expect($purchaseOrder)->toBeInstanceOf(PurchaseOrder::class);
    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Draft);
    expect($purchaseOrder->vendor_id)->toBe($this->vendor->id);
    expect($purchaseOrder->reference)->toBe('TEST-REF-001');
    expect($purchaseOrder->lines)->toHaveCount(1);

    $line = $purchaseOrder->lines->first();
    expect($line->product_id)->toBe($this->product->id);
    expect($line->quantity)->toBe(10.0);
    expect($line->unit_price)->toEqual(Money::of(1000, $this->company->currency->code));
    expect($line->total)->toEqual(Money::of(10000, $this->company->currency->code));
});

it('can confirm a purchase order', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    // Add a line to the purchase order
    $purchaseOrder->lines()->create([
        'product_id' => $this->product->id,
        'description' => 'Test Product',
        'quantity' => 5.0,
        'unit_price' => Money::of(2000, $this->company->currency->code),
        'subtotal' => Money::of(10000, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(10000, $this->company->currency->code),
    ]);

    $service = app(PurchaseOrderService::class);
    $confirmedPO = $service->confirm($purchaseOrder, $this->user);

    expect($confirmedPO->status)->toBe(PurchaseOrderStatus::ToReceive);
    expect($confirmedPO->confirmed_at)->not->toBeNull();
    expect($confirmedPO->po_number)->not->toBeNull();
});

it('can update received quantities', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $line = $purchaseOrder->lines()->create([
        'product_id' => $this->product->id,
        'description' => 'Test Product',
        'quantity' => 10.0,
        'quantity_received' => 0.0,
        'unit_price' => Money::of(1500, $this->company->currency->code),
        'subtotal' => Money::of(15000, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(15000, $this->company->currency->code),
    ]);

    $service = app(PurchaseOrderService::class);

    // Receive partial quantity
    $updatedPO = $service->updateReceivedQuantity($purchaseOrder, $line->id, 6.0);

    expect($updatedPO->status)->toBe(PurchaseOrderStatus::PartiallyReceived);
    expect($updatedPO->lines->first()->quantity_received)->toBe(6.0);

    // Receive remaining quantity
    $fullyReceivedPO = $service->updateReceivedQuantity($updatedPO, $line->id, 4.0);

    expect($fullyReceivedPO->status)->toBe(PurchaseOrderStatus::ToBill);
    expect($fullyReceivedPO->lines->first()->quantity_received)->toBe(10.0);
});

it('can get latest purchase order line for cost determination', function () {
    // Create first purchase order
    $po1 = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'confirmed_at' => now()->subDays(2),
    ]);

    $line1 = $po1->lines()->create([
        'product_id' => $this->product->id,
        'description' => 'First Purchase',
        'quantity' => 10.0,
        'quantity_received' => 5.0,
        'unit_price' => Money::of(1000, $this->company->currency->code),
        'subtotal' => Money::of(10000, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(10000, $this->company->currency->code),
    ]);

    // Create second purchase order (more recent)
    $po2 = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $line2 = $po2->lines()->create([
        'product_id' => $this->product->id,
        'description' => 'Second Purchase',
        'quantity' => 8.0,
        'quantity_received' => 3.0,
        'unit_price' => Money::of(1200, $this->company->currency->code),
        'subtotal' => Money::of(9600, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(9600, $this->company->currency->code),
    ]);

    $service = app(PurchaseOrderService::class);
    $latestLine = $service->getLatestLineForCostDetermination($this->product->id, $this->company->id);

    expect($latestLine)->not->toBeNull();
    expect($latestLine->id)->toBe($line2->id);
    expect($latestLine->unit_price)->toEqual(Money::of(1200, $this->company->currency->code));
});
