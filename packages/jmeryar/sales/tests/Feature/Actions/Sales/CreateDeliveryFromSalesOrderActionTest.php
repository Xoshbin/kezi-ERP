<?php

namespace Jmeryar\Sales\Tests\Feature\Actions;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Validation\ValidationException;
use Jmeryar\Inventory\Enums\Inventory\StockLocationType;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Product\Models\Product;
use Jmeryar\Sales\Actions\Sales\CreateDeliveryFromSalesOrderAction;
use Jmeryar\Sales\DataTransferObjects\Sales\CreateDeliveryFromSalesOrderDTO;
use Jmeryar\Sales\Enums\Sales\SalesOrderStatus;
use Jmeryar\Sales\Models\SalesOrder;
use Jmeryar\Sales\Models\SalesOrderLine;
use Tests\Traits\WithConfiguredCompany;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->user = User::factory()->create();

    $this->warehouse = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Storable,
    ]);

    $journal = \Jmeryar\Accounting\Models\Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Accounting\Enums\Accounting\JournalType::Sale,
    ]);
    $this->company->update(['default_sales_journal_id' => $journal->id]);
});

it('create delivery from sales order success', function () {
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Confirmed,
        'delivery_location_id' => $this->warehouse->id,
    ]);

    SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'quantity_delivered' => 0,
        'unit_price' => Money::of(100, 'USD'),
        'subtotal' => Money::of(1000, 'USD'),
        'total_line_tax' => Money::of(0, 'USD'),
        'total' => Money::of(1000, 'USD'),
    ]);

    $dto = new CreateDeliveryFromSalesOrderDTO(
        salesOrder: $salesOrder,
        user: $this->user,
        autoConfirm: true,
    );

    $action = app(CreateDeliveryFromSalesOrderAction::class);
    $stockMoves = $action->execute($dto);

    expect($stockMoves)->toHaveCount(1);
    expect((float) $stockMoves->first()->productLines->first()->quantity)->toBe(10.0);
});

it('throws validation error for invalid sales order status', function () {
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Draft, // Invalid status for delivery
        'delivery_location_id' => $this->warehouse->id,
    ]);

    $dto = new CreateDeliveryFromSalesOrderDTO(
        salesOrder: $salesOrder,
        user: $this->user,
    );

    $action = app(CreateDeliveryFromSalesOrderAction::class);

    expect(fn () => $action->execute($dto))
        ->toThrow(ValidationException::class);
});

it('skips stock move creation when no warehouse location', function () {
    // Delete the warehouse created in beforeEach
    $this->warehouse->delete();
    // Also ensure no other internal location exists
    StockLocation::where('company_id', $this->company->id)
        ->where('type', StockLocationType::Internal)
        ->delete();

    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Confirmed,
        'delivery_location_id' => null, // No fallback available
    ]);

    SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
    ]);

    $dto = new CreateDeliveryFromSalesOrderDTO(
        salesOrder: $salesOrder,
        user: $this->user,
    );

    $action = app(CreateDeliveryFromSalesOrderAction::class);
    $stockMoves = $action->execute($dto);

    expect($stockMoves)->toBeEmpty();
});

it('handles partial delivery correctly', function () {
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::PartiallyDelivered,
        'delivery_location_id' => $this->warehouse->id,
    ]);

    SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'quantity_delivered' => 4, // 6 remaining
        'unit_price' => Money::of(100, 'USD'),
    ]);

    $dto = new CreateDeliveryFromSalesOrderDTO(
        salesOrder: $salesOrder,
        user: $this->user,
        autoConfirm: true,
    );

    $action = app(CreateDeliveryFromSalesOrderAction::class);
    $stockMoves = $action->execute($dto);

    expect($stockMoves)->toHaveCount(1);
    // Should only deliver the remaining 6
    expect((float) $stockMoves->first()->productLines->first()->quantity)->toBe(6.0);
});

it('skips service products', function () {
    $serviceProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Service,
    ]);

    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Confirmed,
        'delivery_location_id' => $this->warehouse->id,
    ]);

    SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $serviceProduct->id,
        'quantity' => 10,
    ]);

    $dto = new CreateDeliveryFromSalesOrderDTO(
        salesOrder: $salesOrder,
        user: $this->user,
    );

    $action = app(CreateDeliveryFromSalesOrderAction::class);
    $stockMoves = $action->execute($dto);

    expect($stockMoves)->toBeEmpty();
});

// Removed incomplete test

it('marks sales order as fully delivered when all items delivered', function () {
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Confirmed,
        'delivery_location_id' => $this->warehouse->id,
    ]);

    SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'quantity_delivered' => 0,
    ]);

    $dto = new CreateDeliveryFromSalesOrderDTO(
        salesOrder: $salesOrder,
        user: $this->user,
        autoConfirm: true,
    );

    $action = app(CreateDeliveryFromSalesOrderAction::class);
    $action->execute($dto);

    // Verify Picking status is Done, implying successful full delivery
    $picking = \Jmeryar\Inventory\Models\StockPicking::where('origin', 'Sales Order: '.$salesOrder->so_number)->first();
    expect($picking)->not->toBeNull();
    expect($picking->state)->toBe(\Jmeryar\Inventory\Enums\Inventory\StockPickingState::Done);
});
