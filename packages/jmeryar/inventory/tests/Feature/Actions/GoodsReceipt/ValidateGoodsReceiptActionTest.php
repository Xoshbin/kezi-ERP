<?php

namespace Jmeryar\Inventory\Tests\Feature\Actions\GoodsReceipt;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Jmeryar\Inventory\Actions\GoodsReceipt\ValidateGoodsReceiptAction;
use Jmeryar\Inventory\DataTransferObjects\ReceiveGoodsLineDTO;
use Jmeryar\Inventory\DataTransferObjects\ValidateGoodsReceiptDTO;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockPickingState;
use Jmeryar\Inventory\Enums\Inventory\StockPickingType;
use Jmeryar\Inventory\Events\GoodsReceiptValidated;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Models\StockMoveProductLine;
use Jmeryar\Inventory\Models\StockPicking;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Models\PurchaseOrder;
use Jmeryar\Purchase\Models\PurchaseOrderLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->action = app(ValidateGoodsReceiptAction::class);
});

it('validates a goods receipt successfully', function () {
    Event::fake();

    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => \Jmeryar\Foundation\Models\Partner::factory()->create(['company_id' => $this->company->id])->id,
    ]);

    $poLine = PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'quantity_received' => 0,
    ]);

    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Receipt,
        'state' => StockPickingState::Assigned,
        'purchase_order_id' => $po->id,
    ]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'picking_id' => $picking->id,
        'status' => StockMoveStatus::Confirmed,
    ]);

    $moveLine = StockMoveProductLine::factory()->create([
        'stock_move_id' => $move->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'source_type' => PurchaseOrderLine::class,
        'source_id' => $poLine->id,
        'company_id' => $this->company->id,
    ]);

    $dto = new ValidateGoodsReceiptDTO(
        stockPicking: $picking,
        userId: $this->user->id,
        lines: [
            new ReceiveGoodsLineDTO(
                purchaseOrderLineId: $poLine->id,
                quantityToReceive: 10
            ),
        ]
    );

    $result = $this->action->execute($dto);

    expect($result->state)->toBe(StockPickingState::Done)
        ->and($result->grn_number)->not->toBeNull();

    expect($move->refresh()->status)->toBe(StockMoveStatus::Done);

    // Check PO line received quantity
    // Note: It might be updated by both action AND observer, but let's see current behavior.
    expect($poLine->refresh()->quantity_received)->toEqual(10);

    Event::assertDispatched(GoodsReceiptValidated::class, function ($event) use ($picking) {
        return $event->stockPicking->id === $picking->id && count($event->receivedLines) === 1;
    });
});

it('creates a backorder for partial receipt', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $po = PurchaseOrder::factory()->create(['company_id' => $this->company->id]);
    $poLine = PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Receipt,
        'state' => StockPickingState::Assigned,
        'purchase_order_id' => $po->id,
    ]);

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'picking_id' => $picking->id,
    ]);

    StockMoveProductLine::factory()->create([
        'stock_move_id' => $move->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'source_type' => PurchaseOrderLine::class,
        'source_id' => $poLine->id,
        'company_id' => $this->company->id,
    ]);

    $dto = new ValidateGoodsReceiptDTO(
        stockPicking: $picking,
        userId: $this->user->id,
        lines: [
            new ReceiveGoodsLineDTO(
                purchaseOrderLineId: $poLine->id,
                quantityToReceive: 6
            ),
        ],
        createBackorder: true
    );

    $this->action->execute($dto);

    expect($picking->refresh()->state)->toBe(StockPickingState::Done);

    // Check backorder
    $backorder = StockPicking::where('purchase_order_id', $po->id)
        ->where('state', StockPickingState::Assigned)
        ->first();

    expect($backorder)->not->toBeNull()
        ->and($backorder->origin)->toContain('(Backorder)');

    $backorderMove = $backorder->stockMoves()->first();
    $backorderProductLine = $backorderMove->productLines()->first();

    expect($backorderProductLine->quantity)->toEqual(4);
});

it('throws exception when validating non-receipt picking', function () {
    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Internal,
    ]);

    $dto = new ValidateGoodsReceiptDTO($picking, $this->user->id);

    $this->action->execute($dto);
})->throws(\InvalidArgumentException::class, 'Only receipt pickings can be validated as goods receipts.');

it('throws exception when validating already done picking', function () {
    $picking = StockPicking::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockPickingType::Receipt,
        'state' => StockPickingState::Done,
    ]);

    $dto = new ValidateGoodsReceiptDTO($picking, $this->user->id);

    $this->action->execute($dto);
})->throws(\InvalidArgumentException::class, 'Cannot validate a picking that is already done or cancelled.');
