<?php

namespace Jmeryar\Inventory\Tests\Unit\Services;

use App\Models\Company;
use Brick\Money\Money;
use Jmeryar\Inventory\Enums\Inventory\ValuationMethod;
use Jmeryar\Inventory\Services\Inventory\ProductCostAnalysisService;
use Jmeryar\Product\Enums\Products\ProductType;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Enums\Purchases\VendorBillStatus;
use Jmeryar\Purchase\Models\VendorBill;
use Jmeryar\Purchase\Models\VendorBillLine;

it('analyzes vendor bill status correctly with no bills', function () {
    /** @var Company $company */
    $company = Company::factory()->create();
    $service = app(ProductCostAnalysisService::class);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'type' => ProductType::Storable,
    ]);

    $analysis = $service->analyzeVendorBillStatus($product);

    expect($analysis['has_vendor_bills'])->toBeFalse()
        ->and($analysis['draft_count'])->toBe(0)
        ->and($analysis['posted_count'])->toBe(0)
        ->and($analysis['total_lines'])->toBe(0);
});

it('analyzes vendor bill status correctly with draft bills', function () {
    /** @var Company $company */
    $company = Company::factory()->create();
    $service = app(ProductCostAnalysisService::class);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'type' => ProductType::Storable,
    ]);

    /** @var VendorBill $bill */
    $bill = VendorBill::factory()->for($company)->create([
        'status' => VendorBillStatus::Draft,
    ]);

    VendorBillLine::factory()->for($company)->create([
        'vendor_bill_id' => $bill->id,
        'product_id' => $product->id,
    ]);

    $analysis = $service->analyzeVendorBillStatus($product);

    expect($analysis['has_vendor_bills'])->toBeTrue()
        ->and($analysis['draft_count'])->toBe(1)
        ->and($analysis['posted_count'])->toBe(0);
});

it('analyzes vendor bill status correctly with posted invoices', function () {
    /** @var Company $company */
    $company = Company::factory()->create();
    $service = app(ProductCostAnalysisService::class);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'type' => ProductType::Storable,
    ]);

    /** @var VendorBill $bill */
    $bill = VendorBill::factory()->for($company)->create([
        'status' => VendorBillStatus::Posted,
        'posted_at' => now(),
    ]);

    VendorBillLine::factory()->for($company)->create([
        'vendor_bill_id' => $bill->id,
        'product_id' => $product->id,
    ]);

    $analysis = $service->analyzeVendorBillStatus($product);

    expect($analysis['has_vendor_bills'])->toBeTrue()
        ->and($analysis['draft_count'])->toBe(0)
        ->and($analysis['posted_count'])->toBe(1)
        ->and($analysis['latest_posted_bill'])->not->toBeNull();
});

it('checks if ready for inventory movements (AVCO)', function () {
    /** @var Company $company */
    $company = Company::factory()->create();
    $service = app(ProductCostAnalysisService::class);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => \Brick\Money\Money::of(0, $company->currency->code),
    ]);

    // Not ready initially (cost is 0)
    expect($service->isReadyForInventoryMovements($product))->toBeFalse();

    // Set average cost
    $product->average_cost = Money::of(100, $company->currency->code);
    $product->save();

    expect($service->isReadyForInventoryMovements($product))->toBeTrue();
});

it('checks if ready for inventory movements (FIFO)', function () {
    /** @var Company $company */
    $company = Company::factory()->create();
    $service = app(ProductCostAnalysisService::class);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
    ]);

    // Not ready initially (no cost layers)
    expect($service->isReadyForInventoryMovements($product))->toBeFalse();

    // Add cost layer
    $product->inventoryCostLayers()->create([
        'company_id' => $company->id,
        'quantity' => 10,
        'remaining_quantity' => 10,
        'cost_per_unit' => Money::of(100, $company->currency->code),
        'layer_type' => 'purchase',
        'purchase_date' => now(),
        'source_type' => 'Test',
        'source_id' => 1,
    ]);

    expect($service->isReadyForInventoryMovements($product))->toBeTrue();
});

it('provides establishment steps when no bills exist', function () {
    /** @var Company $company */
    $company = Company::factory()->create();
    $service = app(ProductCostAnalysisService::class);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'type' => ProductType::Storable,
    ]);

    $steps = $service->getEstablishmentSteps($product);

    expect($steps)->toBeArray()
        ->and($steps[0])->toContain('Obtain purchase invoices');
});

it('provides establishment steps when draft bills exist', function () {
    /** @var Company $company */
    $company = Company::factory()->create();
    $service = app(ProductCostAnalysisService::class);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'type' => ProductType::Storable,
    ]);

    /** @var VendorBill $bill */
    $bill = VendorBill::factory()->for($company)->create([
        'status' => VendorBillStatus::Draft,
    ]);
    VendorBillLine::factory()->for($company)->create([
        'vendor_bill_id' => $bill->id,
        'product_id' => $product->id,
    ]);

    $steps = $service->getEstablishmentSteps($product);

    expect($steps[0])->toContain('Review the draft vendor bill');
});

it('provides contextual suggestions correctly', function () {
    /** @var Company $company */
    $company = Company::factory()->create();
    $service = app(ProductCostAnalysisService::class);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => \Brick\Money\Money::of(0, $company->currency->code),
    ]);

    $suggestions = $service->getContextualCostSuggestions($product);

    // Should suggest creating bills since none exist
    expect($suggestions)->toContain('Create and post a vendor bill for this product to establish purchase cost');

    // Also since not ready, should contain establishment steps
    expect($suggestions)->toContain('1. Obtain purchase invoices from your supplier for this product');
});

it('provides cost status explanation for AVCO', function () {
    /** @var Company $company */
    $company = Company::factory()->create();
    $service = app(ProductCostAnalysisService::class);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
    ]);

    $explanation = $service->getCostStatusExplanation($product);

    expect($explanation)->toContain('Using AVCO valuation method')
        ->toContain('No vendor bills found');
});
