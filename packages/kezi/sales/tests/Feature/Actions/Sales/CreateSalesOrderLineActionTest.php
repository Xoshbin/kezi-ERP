<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Tax;
use Kezi\Product\Models\Product;
use Kezi\Sales\Actions\Sales\CreateSalesOrderLineAction;
use Kezi\Sales\DataTransferObjects\Sales\CreateSalesOrderLineDTO;
use Kezi\Sales\Models\SalesOrder;
use Kezi\Sales\Models\SalesOrderLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateSalesOrderLineAction::class);
});

it('can create a sales order line and calculate totals', function () {
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $tax = Tax::factory()->create(['company_id' => $this->company->id, 'rate' => 10]); // 10% tax

    $dto = new CreateSalesOrderLineDTO(
        description: 'Test Line',
        quantity: 2.0,
        unit_price: Money::of(100, $this->company->currency->code),
        product_id: $product->id,
        tax_id: $tax->id,
        expected_delivery_date: now(),
        notes: 'Test Note',
    );

    $line = $this->action->execute($salesOrder, $dto);

    expect($line)->toBeInstanceOf(SalesOrderLine::class)
        ->and($line->sales_order_id)->toBe($salesOrder->id)
        ->and($line->product_id)->toBe($product->id)
        ->and((float) $line->quantity)->toBe(2.0)
        ->and($line->unit_price->getAmount()->toFloat())->toBe(100.0)
        ->and($line->subtotal->getAmount()->toFloat())->toBe(200.0) // 2 * 100
        ->and($line->total_line_tax->getAmount()->toFloat())->toBe(20.0) // 200 * 0.10
        ->and($line->total->getAmount()->toFloat())->toBe(220.0); // 200 + 20
});

it('can create a sales order line without tax', function () {
    $salesOrder = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateSalesOrderLineDTO(
        description: 'Test Line No Tax',
        quantity: 5.0,
        unit_price: Money::of(10, $this->company->currency->code),
        product_id: $product->id,
        tax_id: null,
        expected_delivery_date: now(),
    );

    $line = $this->action->execute($salesOrder, $dto);

    expect($line->tax_id)->toBeNull()
        ->and($line->subtotal->getAmount()->toFloat())->toBe(50.0)
        ->and($line->total_line_tax->getAmount()->toFloat())->toBe(0.0)
        ->and($line->total->getAmount()->toFloat())->toBe(50.0);
});
