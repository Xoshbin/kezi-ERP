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

it('demonstrates complete cost determination workflow from vendor bill to manual stock move', function () {
    // Step 1: Create a vendor bill with product to establish cost history
    $vendorBill = \App\Models\VendorBill::factory()->for($this->company)->create([
        'status' => \App\Enums\Purchases\VendorBillStatus::Posted,
        'currency_id' => $this->company->currency->id,
        'exchange_rate_at_creation' => 1.0,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(0, 'IQD'), // No initial average cost
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    \App\Models\VendorBillLine::factory()->create([
        'vendor_bill_id' => $vendorBill->id,
        'product_id' => $product->id,
        'quantity' => 100,
        'unit_price' => Money::of(50000, 'IQD'),
        'total_line_tax' => Money::of(0, 'IQD'),
    ]);

    // Step 2: Create stock move from vendor bill (should use vendor bill cost)
    $vendorBillStockMove = \App\Models\StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'source_type' => \App\Models\VendorBill::class,
        'source_id' => $vendorBill->id,
    ]);

    $vendorBillProductLine = \App\Models\StockMoveProductLine::factory()->create([
        'company_id' => $this->company->id,
        'stock_move_id' => $vendorBillStockMove->id,
        'product_id' => $product->id,
        'quantity' => 100,
        'from_location_id' => $this->fromLocation->id,
        'to_location_id' => $this->toLocation->id,
    ]);

    // Process the vendor bill stock move
    app(\App\Actions\Inventory\CreateJournalEntryForStockMoveAction::class)->execute($vendorBillStockMove, $this->user);

    // Verify vendor bill cost was used and tracked
    $vendorBillValuation = StockMoveValuation::where('stock_move_id', $vendorBillStockMove->id)->first();
    expect($vendorBillValuation)->not->toBeNull();
    expect($vendorBillValuation->cost_source)->toBe(CostSource::VendorBill);
    expect($vendorBillValuation->cost_source_reference)->toBe("VendorBill:{$vendorBill->id}");

    // Refresh product to see updated average cost
    $product->refresh();
    expect($product->average_cost)->toEqual(Money::of(50000, 'IQD'));

    // Step 3: Create manual stock move (should use average cost)
    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        created_by_user_id: $this->user->id,
        product_lines: [
            new CreateStockMoveProductLineDTO(
                product_id: $product->id,
                quantity: 50.0,
                from_location_id: $this->fromLocation->id,
                to_location_id: $this->toLocation->id,
                description: 'Manual stock receipt',
            ),
        ],
        reference: 'SM-MANUAL-001',
        description: 'Manual stock receipt using average cost',
    );

    $manualStockMove = app(CreateStockMoveAction::class)->execute($dto);

    // Verify manual stock move used average cost and tracked source
    $manualValuation = StockMoveValuation::where('stock_move_id', $manualStockMove->id)->first();
    expect($manualValuation)->not->toBeNull();
    expect($manualValuation->cost_source)->toBe(CostSource::AverageCost);
    expect($manualValuation->cost_source_reference)->toBe("Product:{$product->id}");
    expect($manualValuation->cost_warnings)->toBeArray();

    // Step 4: Test cost validation service
    $validationResult = $this->costValidationService->validateCostAvailability($product, StockMoveType::Incoming);
    expect($validationResult->isValid())->toBeTrue();
    expect($validationResult->getMessage())->toContain('Cost can be determined');

    // Step 5: Test cost preview
    $previewResult = $this->costValidationService->getCostPreview($product, 25.0, StockMoveType::Incoming);
    expect($previewResult->isValid())->toBeTrue();
    expect($previewResult->getUnitCost())->toEqual(Money::of(50000, 'IQD'));
    expect($previewResult->getTotalCost())->toEqual(Money::of(1250000, 'IQD')); // 25 * 50000
    expect($previewResult->getCostSource())->toBe(CostSource::AverageCost);
});

it('demonstrates fallback cost determination with warnings', function () {
    // Create product with only unit price (no average cost or cost layers)
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(0, 'IQD'),
        'unit_price' => Money::of(75000, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    // Test cost determination with fallbacks allowed
    $stockMove = \App\Models\StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'source_type' => null,
        'source_id' => null,
    ]);

    $costResult = $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced($product, $stockMove, true);

    expect($costResult->cost)->toEqual(Money::of(75000, 'IQD'));
    expect($costResult->source)->toBe(CostSource::UnitPrice);
    expect($costResult->hasWarnings())->toBeTrue();
    expect($costResult->warnings)->toContain('Using product unit price as cost - this may not reflect actual purchase cost');

    // Test validation service recognizes the warning
    $validationResult = $this->costValidationService->validateCostAvailability($product, StockMoveType::Incoming);
    expect($validationResult->isValid())->toBeFalse();
    expect($validationResult->getSuggestedActions())->toContain('Post a vendor bill for this product to establish cost');
});

it('demonstrates complete failure scenario with actionable suggestions', function () {
    // Create product without any cost information
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(0, 'IQD'),
        'unit_price' => Money::of(0, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $stockMove = \App\Models\StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'source_type' => null,
        'source_id' => null,
    ]);

    // Test that enhanced method throws detailed exception
    expect(fn() => $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced($product, $stockMove))
        ->toThrow(InsufficientCostInformationException::class);

    // Test validation service provides actionable suggestions
    $validationResult = $this->costValidationService->validateCostAvailability($product, StockMoveType::Incoming);
    expect($validationResult->isValid())->toBeFalse();
    expect($validationResult->getSuggestedActions())->toContain('Post a vendor bill for this product to establish cost');
    expect($validationResult->getSuggestedActions())->toContain('Set a positive average cost on the product');

    // Test that creating a stock move with this product fails gracefully
    $dto = new CreateStockMoveDTO(
        company_id: $this->company->id,
        move_type: StockMoveType::Incoming,
        status: StockMoveStatus::Done,
        move_date: now(),
        created_by_user_id: $this->user->id,
        product_lines: [
            new CreateStockMoveProductLineDTO(
                product_id: $product->id,
                quantity: 10.0,
                from_location_id: $this->fromLocation->id,
                to_location_id: $this->toLocation->id,
            ),
        ],
    );

    expect(fn() => app(CreateStockMoveAction::class)->execute($dto))
        ->toThrow(InsufficientCostInformationException::class);
});

it('demonstrates FIFO cost layer fallback', function () {
    // Create FIFO product with cost layer but no average cost
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'average_cost' => Money::of(0, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $costLayer = InventoryCostLayer::factory()->create([
        'product_id' => $product->id,
        'cost_per_unit' => Money::of(45000, 'IQD'),
        'remaining_quantity' => 20.0,
    ]);

    $stockMove = \App\Models\StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'source_type' => null,
        'source_id' => null,
    ]);

    $costResult = $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced($product, $stockMove);

    expect($costResult->cost)->toEqual(Money::of(45000, 'IQD'));
    expect($costResult->source)->toBe(CostSource::CostLayer);
    expect($costResult->reference)->toBe("CostLayer:{$costLayer->id}");
    expect($costResult->hasWarnings())->toBeFalse();
});
