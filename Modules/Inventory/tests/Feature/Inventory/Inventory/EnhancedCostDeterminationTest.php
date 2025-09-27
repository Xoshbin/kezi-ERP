<?php

use App\Actions\Inventory\CreateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use App\Enums\Inventory\CostSource;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Exceptions\Inventory\InsufficientCostInformationException;
use App\Models\InventoryCostLayer;
use App\Models\Product;
use App\Models\StockMove;
use App\Models\StockMoveValuation;
use App\Services\Inventory\CostValidationService;
use App\Services\Inventory\InventoryValuationService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->costValidationService = app(CostValidationService::class);
    $this->inventoryValuationService = app(InventoryValuationService::class);

    // Create required accounts
    $this->inventoryAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'name' => 'Inventory Asset',
        'type' => 'current_assets',
    ]);
    $this->cogsAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'name' => 'Cost of Goods Sold',
        'type' => 'expense',
    ]);
    $this->stockInputAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'name' => 'Stock Input',
        'type' => 'current_liabilities',
    ]);

    // Create required locations
    $this->fromLocation = \App\Models\StockLocation::factory()->for($this->company)->create([
        'name' => 'Vendor Location',
        'type' => \App\Enums\Inventory\StockLocationType::Vendor,
    ]);
    $this->toLocation = \App\Models\StockLocation::factory()->for($this->company)->create([
        'name' => 'Internal Location',
        'type' => \App\Enums\Inventory\StockLocationType::Internal,
    ]);
});

it('uses vendor bill cost as highest priority source', function () {
    // Arrange: Create a vendor bill with product
    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($this->company)->create([
        'status' => \App\Enums\Purchases\VendorBillStatus::Posted,
        'currency_id' => $this->company->currency->id,
        'exchange_rate_at_creation' => 1.0,
    ]);

    $product = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(30000, 'IQD'), // Lower than vendor bill cost
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    \App\Models\VendorBillLine::factory()->create([
        'vendor_bill_id' => $vendorBill->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => Money::of(50000, 'IQD'), // Higher than average cost
        'total_line_tax' => Money::of(0, 'IQD'),
    ]);

    // Create stock move from vendor bill
    $stockMove = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'source_type' => \Modules\Purchase\Models\VendorBill::class,
        'source_id' => $vendorBill->id,
    ]);

    // Act: Calculate cost
    $costResult = $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced($product, $stockMove);

    // Assert: Should use vendor bill cost, not average cost
    expect($costResult->cost)->toEqual(Money::of(50000, 'IQD'));
    expect($costResult->source)->toBe(CostSource::VendorBill);
    expect($costResult->reference)->toBe("VendorBill:{$vendorBill->id}");
});

it('falls back to average cost when vendor bill cost unavailable', function () {
    // Arrange: Create product with average cost but no vendor bill
    $product = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(40000, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $stockMove = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'source_type' => null,
        'source_id' => null,
    ]);

    // Act: Calculate cost
    $costResult = $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced($product, $stockMove);

    // Assert: Should use average cost
    expect($costResult->cost)->toEqual(Money::of(40000, 'IQD'));
    expect($costResult->source)->toBe(CostSource::AverageCost);
    expect($costResult->reference)->toBe("Product:{$product->id}");
});

it('falls back to cost layer for FIFO/LIFO products', function () {
    // Arrange: Create FIFO product with cost layer but no average cost
    $product = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'average_cost' => Money::of(0, 'IQD'), // Zero average cost
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $costLayer = InventoryCostLayer::factory()->create([
        'product_id' => $product->id,
        'cost_per_unit' => Money::of(35000, 'IQD'),
        'remaining_quantity' => 5.0,
    ]);

    $stockMove = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'source_type' => null,
        'source_id' => null,
    ]);

    // Act: Calculate cost
    $costResult = $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced($product, $stockMove);

    // Assert: Should use cost layer
    expect($costResult->cost)->toEqual(Money::of(35000, 'IQD'));
    expect($costResult->source)->toBe(CostSource::CostLayer);
    expect($costResult->reference)->toBe("CostLayer:{$costLayer->id}");
});

it('uses unit price fallback when allowed', function () {
    // Arrange: Create product with only unit price
    $product = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(0, 'IQD'),
        'unit_price' => Money::of(60000, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $stockMove = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'source_type' => null,
        'source_id' => null,
    ]);

    // Act: Calculate cost with fallbacks allowed
    $costResult = $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced($product, $stockMove, true);

    // Assert: Should use unit price with warning
    expect($costResult->cost)->toEqual(Money::of(60000, 'IQD'));
    expect($costResult->source)->toBe(CostSource::UnitPrice);
    expect($costResult->hasWarnings())->toBeTrue();
    expect($costResult->warnings)->toContain('Using product unit price as cost - this may not reflect actual purchase cost');
});

it('throws detailed exception when no cost sources available', function () {
    // Arrange: Create product without any cost information
    $product = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(0, 'IQD'),
        'unit_price' => Money::of(0, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $stockMove = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'source_type' => null,
        'source_id' => null,
    ]);

    // Act & Assert: Should throw detailed exception
    expect(fn() => $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced($product, $stockMove))
        ->toThrow(InsufficientCostInformationException::class);
});

it('tracks cost source in stock move valuations', function () {
    // Arrange: Create product with average cost
    $product = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(45000, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        created_by_user_id: $this->user->id,
        product_lines: [
            new CreateStockMoveProductLineDTO(
                product_id: $product->id,
                quantity: 5.0,
                from_location_id: $this->fromLocation->id,
                to_location_id: $this->toLocation->id,
            ),
        ],
    );

    // Act: Create stock move
    $stockMove = app(\Modules\Inventory\Actions\Inventory\CreateStockMoveAction::class)->execute($dto);

    // Assert: Stock move valuation should track cost source
    $valuation = StockMoveValuation::where('stock_move_id', $stockMove->id)->first();
    expect($valuation)->not->toBeNull();
    expect($valuation->cost_source)->toBe(CostSource::AverageCost);
    expect($valuation->cost_source_reference)->toBe("Product:{$product->id}");
    expect($valuation->cost_warnings)->toBeArray();
});

it('validates cost availability correctly', function () {
    // Arrange: Create products with and without cost information
    $productWithCost = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'average_cost' => Money::of(50000, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $productWithoutCost = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'average_cost' => Money::of(0, 'IQD'),
        'unit_price' => Money::of(0, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    // Act & Assert: Validation should work correctly
    $validResult = $this->costValidationService->validateCostAvailability($productWithCost, StockMoveType::Incoming);
    expect($validResult->isValid())->toBeTrue();

    $invalidResult = $this->costValidationService->validateCostAvailability($productWithoutCost, StockMoveType::Incoming);
    expect($invalidResult->isValid())->toBeFalse();
    expect($invalidResult->getSuggestedActions())->not->toBeEmpty();
});
