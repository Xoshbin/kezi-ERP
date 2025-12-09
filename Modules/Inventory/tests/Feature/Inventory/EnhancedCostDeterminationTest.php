<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Modules\Inventory\Enums\Inventory\CostSource;
use Modules\Inventory\Enums\Inventory\StockLocationType;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Inventory\Exceptions\Inventory\InsufficientCostInformationException;
use Modules\Inventory\Models\InventoryCostLayer;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockMoveValuation;
use Modules\Inventory\Services\Inventory\CostValidationService;
use Modules\Inventory\Services\Inventory\InventoryValuationService;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->costValidationService = app(CostValidationService::class);
    $this->inventoryValuationService = app(InventoryValuationService::class);

    // Create required accounts
    $this->inventoryAccount = Account::factory()->for($this->company)->create([
        'name' => 'Inventory Asset',
        'type' => 'current_assets',
    ]);
    $this->cogsAccount = Account::factory()->for($this->company)->create([
        'name' => 'Cost of Goods Sold',
        'type' => 'expense',
    ]);
    $this->stockInputAccount = Account::factory()->for($this->company)->create([
        'name' => 'Stock Input',
        'type' => 'current_liabilities',
    ]);

    // Create required locations
    $this->fromLocation = StockLocation::factory()->for($this->company)->create([
        'name' => 'Vendor Location',
        'type' => StockLocationType::Vendor,
    ]);
    $this->toLocation = StockLocation::factory()->for($this->company)->create([
        'name' => 'Internal Location',
        'type' => StockLocationType::Internal,
    ]);
});

it('uses vendor bill cost as highest priority source', function () {
    // Arrange: Create a vendor bill with product
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'status' => VendorBillStatus::Posted,
        'currency_id' => $this->company->currency->id,
        'exchange_rate_at_creation' => 1.0,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(30000, 'IQD'), // Lower than vendor bill cost
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    VendorBillLine::factory()->create([
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
        'source_type' => VendorBill::class,
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
    $product = Product::factory()->create([
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
    $product = Product::factory()->create([
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
    $product = Product::factory()->create([
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
    $product = Product::factory()->create([
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
    expect(fn () => $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced($product, $stockMove))
        ->toThrow(InsufficientCostInformationException::class);
});

it('tracks cost source in stock move valuations', function () {
    // Arrange: Create product with average cost
    $product = Product::factory()->create([
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
    $productWithCost = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'average_cost' => Money::of(50000, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $productWithoutCost = Product::factory()->create([
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
