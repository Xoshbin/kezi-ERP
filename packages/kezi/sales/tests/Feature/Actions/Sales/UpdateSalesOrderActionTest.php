<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Tax;
use Kezi\Product\Models\Product;
use Kezi\Sales\Actions\Sales\UpdateSalesOrderAction;
use Kezi\Sales\DataTransferObjects\Sales\CreateSalesOrderLineDTO;
use Kezi\Sales\DataTransferObjects\Sales\UpdateSalesOrderDTO;
use Kezi\Sales\Enums\Sales\SalesOrderStatus;
use Kezi\Sales\Models\SalesOrder;
use Kezi\Sales\Models\SalesOrderLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(UpdateSalesOrderAction::class);
});

it('can update sales order header and lines', function () {
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'notes' => 'Old Notes',
        'status' => SalesOrderStatus::Draft,
    ]);

    $existingLine = SalesOrderLine::factory()->create([
        'sales_order_id' => $salesOrder->id,
        'quantity' => 1,
    ]);

    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $tax = Tax::factory()->create(['company_id' => $this->company->id]);

    $lineDto = new CreateSalesOrderLineDTO(
        description: 'New Line',
        quantity: 5.0,
        unit_price: Money::of(100, $this->company->currency->code),
        product_id: $product->id,
        tax_id: $tax->id,
        expected_delivery_date: now(),
    );

    $dto = new UpdateSalesOrderDTO(
        salesOrder: $salesOrder,
        customer_id: $salesOrder->customer_id,
        currency_id: $salesOrder->currency_id,
        reference: 'REF-UPDATED',
        so_date: now(),
        expected_delivery_date: now()->addMonth(),
        exchange_rate_at_creation: 1.0,
        notes: 'Updated Notes',
        lines: [$lineDto],
        terms_and_conditions: 'New Terms',
        delivery_location_id: null,
    );

    $this->action->execute($dto);

    $salesOrder->refresh();

    expect($salesOrder->notes)->toBe('Updated Notes')
        ->and($salesOrder->reference)->toBe('REF-UPDATED')
        ->and($salesOrder->lines)->toHaveCount(1);

    $newLine = $salesOrder->lines->first();
    expect((float) $newLine->quantity)->toBe(5.0)
        ->and($newLine->description)->toBe('New Line');
});

it('prevents update if sales order is confirmed', function () {
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Confirmed,
    ]);

    $dto = new UpdateSalesOrderDTO(
        salesOrder: $salesOrder,
        customer_id: $salesOrder->customer_id,
        currency_id: $salesOrder->currency_id,
        reference: 'REF-UPDATED',
        so_date: now(),
        expected_delivery_date: now(),
        exchange_rate_at_creation: 1.0,
        lines: [],
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(\Kezi\Foundation\Exceptions\UpdateNotAllowedException::class);
});

it('enforces lock date on update', function () {
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Draft,
        'so_date' => now()->subMonth(),
    ]);

    // Create a lock date entry
    \Kezi\Accounting\Models\LockDate::create([
        'company_id' => $this->company->id,
        'lock_type' => \Kezi\Accounting\Enums\Accounting\LockDateType::AllUsers,
        'locked_until' => now()->subDay(),
    ]);

    $dto = new UpdateSalesOrderDTO(
        salesOrder: $salesOrder,
        customer_id: $salesOrder->customer_id,
        currency_id: $salesOrder->currency_id,
        reference: 'REF-UPDATED',
        so_date: now()->subMonth(), // Try to update with a date in locked period
        expected_delivery_date: now(),
        exchange_rate_at_creation: 1.0,
        lines: [],
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(\Kezi\Accounting\Exceptions\PeriodIsLockedException::class);
});
