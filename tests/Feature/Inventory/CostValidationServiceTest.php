<?php

use App\DataTransferObjects\Inventory\CostPreviewResult;
use App\DataTransferObjects\Inventory\CostValidationResult;
use App\Enums\Inventory\CostSource;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Models\Product;
use App\Models\StockMove;
use App\Services\Inventory\CostValidationService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->costValidationService = app(CostValidationService::class);

    // Create required accounts
    $this->inventoryAccount = \App\Models\Account::factory()->for($this->company)->create([
        'name' => 'Inventory Asset',
        'type' => 'current_assets',
    ]);
    $this->cogsAccount = \App\Models\Account::factory()->for($this->company)->create([
        'name' => 'Cost of Goods Sold',
        'type' => 'expense',
    ]);
    $this->stockInputAccount = \App\Models\Account::factory()->for($this->company)->create([
        'name' => 'Stock Input',
        'type' => 'current_liabilities',
    ]);
});

it('validates cost availability for product with average cost', function () {
    // Arrange: Create a product with average cost
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(50000, 'IQD'), // 500.00 IQD
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    // Act: Validate cost availability
    $result = $this->costValidationService->validateCostAvailability(
        $product,
        StockMoveType::Incoming
    );

    // Assert: Should be valid
    expect($result)->toBeInstanceOf(CostValidationResult::class);
    expect($result->isValid())->toBeTrue();
    expect($result->getCostResult())->not->toBeNull();
    expect($result->getCostResult()->source)->toBe(CostSource::AverageCost);
});

it('validates cost unavailability for product without cost information', function () {
    // Arrange: Create a product without cost information
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(0, 'IQD'), // Zero cost
        'unit_price' => Money::of(0, 'IQD'), // Zero price
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    // Act: Validate cost availability
    $result = $this->costValidationService->validateCostAvailability(
        $product,
        StockMoveType::Incoming
    );

    // Assert: Should be invalid
    expect($result)->toBeInstanceOf(CostValidationResult::class);
    expect($result->isValid())->toBeFalse();
    expect($result->getSuggestedActions())->not->toBeEmpty();
});

it('provides cost preview for valid product', function () {
    // Arrange: Create a product with average cost
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(50000, 'IQD'), // 500.00 IQD
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $quantity = 10.0;

    // Act: Get cost preview
    $result = $this->costValidationService->getCostPreview(
        $product,
        $quantity,
        StockMoveType::Incoming
    );

    // Assert: Should provide valid preview
    expect($result)->toBeInstanceOf(CostPreviewResult::class);
    expect($result->isValid())->toBeTrue();
    expect($result->getUnitCost())->toEqual(Money::of(50000, 'IQD'));
    expect($result->getTotalCost())->toEqual(Money::of(500000, 'IQD')); // 50000 * 10
    expect($result->getCostSource())->toBe(CostSource::AverageCost);
});

it('detects cost information availability correctly', function () {
    // Arrange: Create products with and without cost information
    $productWithCost = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'average_cost' => Money::of(50000, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $productWithoutCost = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'average_cost' => Money::of(0, 'IQD'), // Zero cost
        'unit_price' => Money::of(0, 'IQD'), // Zero price
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    // Act & Assert
    expect($this->costValidationService->hasAnyCostInformation($productWithCost))->toBeTrue();
    expect($this->costValidationService->hasAnyCostInformation($productWithoutCost))->toBeFalse();
});

it('provides appropriate suggested actions', function () {
    // Arrange: Create a product without cost information
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(0, 'IQD'), // Zero cost
        'unit_price' => Money::of(100000, 'IQD'), // Has unit price
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    // Act: Get suggested actions
    $suggestions = $this->costValidationService->getSuggestedActions($product);

    // Assert: Should include relevant suggestions
    expect($suggestions)->toContain('Set a positive average cost on the product');
    expect($suggestions)->toContain('Post a vendor bill for this product to establish cost');
    expect($suggestions)->toContain('Enable unit price fallback in system settings (less reliable)');
});
