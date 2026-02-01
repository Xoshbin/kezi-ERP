<?php

use App\Models\User;
use Brick\Money\Money;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Models\Product;
use Jmeryar\Sales\Actions\Sales\ConfirmSalesOrderAction;
use Jmeryar\Sales\Enums\Sales\SalesOrderStatus;
use Jmeryar\Sales\Models\SalesOrder;
use Jmeryar\Sales\Models\SalesOrderLine;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->setupWithConfiguredCompany();
    $this->user = User::factory()->create();
    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->partner = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'customer',
    ]);

    /** @var \Jmeryar\Product\Models\Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->product = $product;
});

test('it can confirm a draft sales order', function () {
    /** @var \Tests\TestCase $this */
    // Manually create a draft sales order
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->partner->id,
        'currency_id' => $this->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => SalesOrderStatus::Draft,
    ]);

    // Add a line
    SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'product_id' => $this->product->id,
        'description' => 'Test Product',
        'quantity' => 10,
        'unit_price' => Money::of(100, 'USD'), // Minor units
        'subtotal' => Money::of(1000, 'USD'),
        'total' => Money::of(1000, 'USD'),
        'total_line_tax' => Money::of(0, 'USD'),
    ]);

    $action = app(ConfirmSalesOrderAction::class);
    $confirmedOrder = $action->execute($salesOrder, $this->user);

    expect($confirmedOrder->status)->toBe(SalesOrderStatus::Confirmed)
        ->and($confirmedOrder->so_number)->not->toBeNull()
        ->and($confirmedOrder->confirmed_at)->not->toBeNull();
});

test('it does not confirm an already confirmed sales order', function () {
    /** @var \Tests\TestCase $this */
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->partner->id,
        'currency_id' => $this->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => SalesOrderStatus::Confirmed,
        'so_number' => 'SO-001',
    ]);

    $action = app(ConfirmSalesOrderAction::class);
    $order = $action->execute($salesOrder, $this->user);

    // Should remain unchanged mainly (implementation returns early)
    expect($order->status)->toBe(SalesOrderStatus::Confirmed);
});

test('it cannot confirm a non-draft sales order', function () {
    /** @var \Tests\TestCase $this */
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->partner->id,
        'currency_id' => $this->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => SalesOrderStatus::Cancelled,
    ]);

    $action = app(ConfirmSalesOrderAction::class);
    $result = $action->execute($salesOrder, $this->user);

    expect($result->status)->toBe(SalesOrderStatus::Cancelled)
        ->and($result->confirmed_at)->toBeNull();
});
