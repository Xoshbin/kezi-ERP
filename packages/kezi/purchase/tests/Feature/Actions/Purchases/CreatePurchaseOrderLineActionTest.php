<?php

declare(strict_types=1);

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Tax;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\CreatePurchaseOrderLineAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderLineDTO;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrderLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('created a purchase order line with valid data (Happy Path)', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var \Kezi\Foundation\Models\Currency $currency */
    $currency = \Kezi\Foundation\Models\Currency::factory()->createSafely(['code' => 'USD']);

    /** @var \Kezi\Purchase\Models\PurchaseOrder $purchaseOrder */
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);

    /** @var \Kezi\Product\Models\Product $product */
    $product = Product::factory()->create([
        'company_id' => $company->id,
    ]);

    $dto = new CreatePurchaseOrderLineDTO(
        product_id: $product->id,
        description: 'Test Product Description',
        quantity: 10.0,
        unit_price: Money::of(150, 'USD'),
        tax_id: null,
        notes: 'Test Notes'
    );

    $action = app(CreatePurchaseOrderLineAction::class);
    $line = $action->execute($purchaseOrder, $dto);

    expect($line)->toBeInstanceOf(PurchaseOrderLine::class)
        ->and($line->product_id)->toBe($product->id)
        ->and($line->quantity)->toBe(10.0)
        ->and($line->unit_price->getAmount()->toFloat())->toBe(150.0)
        ->and($line->subtotal->getAmount()->toFloat())->toBe(1500.0) // 10 * 150
        ->and($line->total->getAmount()->toFloat())->toBe(1500.0)
        ->and($line->notes)->toBe('Test Notes');

    \Pest\Laravel\assertDatabaseHas('purchase_order_lines', [
        'id' => $line->id,
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => $product->id,
        'quantity' => 10.0,
    ]);
});

it('updates parent purchase order totals after line creation', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var \Kezi\Foundation\Models\Currency $currency */
    $currency = \Kezi\Foundation\Models\Currency::factory()->createSafely(['code' => 'USD']);

    /** @var \Kezi\Purchase\Models\PurchaseOrder $purchaseOrder */
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'total_amount' => Money::of(0, 'USD'), // Start with 0
    ]);

    /** @var \Kezi\Product\Models\Product $product */
    $product = Product::factory()->create([
        'company_id' => $company->id,
    ]);

    $dto = new CreatePurchaseOrderLineDTO(
        product_id: $product->id,
        description: 'Test Product',
        quantity: 5.0,
        unit_price: Money::of(100, 'USD'),
        tax_id: null
    );

    $action = app(CreatePurchaseOrderLineAction::class);
    $action->execute($purchaseOrder, $dto);

    // Refresh PO to check calculated totals
    $purchaseOrder->refresh();

    // Line total: 5 * 100 = 500
    expect($purchaseOrder->total_amount->getAmount()->toFloat())->toBe(500.0);
});

it('calculates tax correctly when tax is provided', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var \Kezi\Foundation\Models\Currency $currency */
    $currency = \Kezi\Foundation\Models\Currency::factory()->createSafely(['code' => 'USD']);

    /** @var \Kezi\Purchase\Models\PurchaseOrder $purchaseOrder */
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);

    /** @var \Kezi\Product\Models\Product $product */
    $product = Product::factory()->create([
        'company_id' => $company->id,
    ]);

    /** @var \Kezi\Accounting\Models\Tax $tax */
    $tax = Tax::factory()->create([
        'company_id' => $company->id,
        'rate' => 10.0, // 10% tax
    ]);

    $dto = new CreatePurchaseOrderLineDTO(
        product_id: $product->id,
        description: 'Taxed Product',
        quantity: 10.0,
        unit_price: Money::of(100, 'USD'),
        tax_id: $tax->id
    );

    $action = app(CreatePurchaseOrderLineAction::class);
    /** @var \Kezi\Purchase\Models\PurchaseOrderLine $line */
    $line = $action->execute($purchaseOrder, $dto);

    // Subtotal: 10 * 100 = 1000
    // Tax: 10% of 1000 = 100
    // Total: 1100

    expect($line->tax_id)->toBe($tax->id)
        ->and($line->subtotal->getAmount()->toFloat())->toBe(1000.0)
        ->and($line->total_line_tax->getAmount()->toFloat())->toBe(100.0)
        ->and($line->total->getAmount()->toFloat())->toBe(1100.0);
});

it('handles zero quantity or price correctly', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var \Kezi\Foundation\Models\Currency $currency */
    $currency = \Kezi\Foundation\Models\Currency::factory()->createSafely(['code' => 'USD']);

    /** @var \Kezi\Purchase\Models\PurchaseOrder $purchaseOrder */
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);
    /** @var \Kezi\Product\Models\Product $product */
    $product = Product::factory()->create(['company_id' => $company->id]);

    // Zero Quantity
    $dtoZeroQty = new CreatePurchaseOrderLineDTO(
        product_id: $product->id,
        description: 'Zero Qty',
        quantity: 0.0,
        unit_price: Money::of(100, 'USD')
    );

    $action = app(CreatePurchaseOrderLineAction::class);
    $lineZeroQty = $action->execute($purchaseOrder, $dtoZeroQty);

    expect($lineZeroQty->subtotal->isZero())->toBeTrue()
        ->and($lineZeroQty->total->isZero())->toBeTrue();

    // Zero Price
    $dtoZeroPrice = new CreatePurchaseOrderLineDTO(
        product_id: $product->id,
        description: 'Zero Price',
        quantity: 10.0,
        unit_price: Money::of(0, 'USD')
    );

    $lineZeroPrice = $action->execute($purchaseOrder, $dtoZeroPrice);

    expect($lineZeroPrice->subtotal->isZero())->toBeTrue()
        ->and($lineZeroPrice->total->isZero())->toBeTrue();
});

it('ensures relationship integrity', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var \Kezi\Foundation\Models\Currency $currency */
    $currency = \Kezi\Foundation\Models\Currency::factory()->createSafely(['code' => 'USD']);

    /** @var \Kezi\Purchase\Models\PurchaseOrder $purchaseOrder */
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);
    /** @var \Kezi\Product\Models\Product $product */
    $product = Product::factory()->create(['company_id' => $company->id]);

    $dto = new CreatePurchaseOrderLineDTO(
        product_id: $product->id,
        description: 'Rel Test',
        quantity: 1.0,
        unit_price: Money::of(50, 'USD')
    );

    $action = app(CreatePurchaseOrderLineAction::class);
    $line = $action->execute($purchaseOrder, $dto);

    expect($line->purchaseOrder->is($purchaseOrder))->toBeTrue()
        ->and($line->product->is($product))->toBeTrue();
});
