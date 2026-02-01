<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Inventory\Actions\Inventory\CreateJournalEntryForStockMoveAction;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Kezi\Inventory\Enums\Inventory\CostSource;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Exceptions\Inventory\InsufficientCostInformationException;
use Kezi\Inventory\Models\InventoryCostLayer;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveProductLine;
use Kezi\Inventory\Models\StockMoveValuation;
use Kezi\Inventory\Services\Inventory\CostValidationService;
use Kezi\Inventory\Services\Inventory\InventoryValuationService;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillLine;
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

it('demonstrates complete cost determination workflow from vendor bill to manual stock move', function () {
    // Step 1: Create a vendor bill with product to establish cost history
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'status' => VendorBillStatus::Posted,
        'currency_id' => $this->company->currency->id,
        'exchange_rate_at_creation' => 1.0,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(0, 'IQD'), // No initial average cost
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    VendorBillLine::factory()->create([
        'vendor_bill_id' => $vendorBill->id,
        'product_id' => $product->id,
        'quantity' => 100,
        'unit_price' => Money::of(50000, 'IQD'),
        'total_line_tax' => Money::of(0, 'IQD'),
    ]);

    // Step 2: Create stock move from vendor bill (should use vendor bill cost)
    $vendorBillStockMove = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
    ]);

    $vendorBillProductLine = StockMoveProductLine::factory()->create([
        'company_id' => $this->company->id,
        'stock_move_id' => $vendorBillStockMove->id,
        'product_id' => $product->id,
        'quantity' => 100,
        'from_location_id' => $this->fromLocation->id,
        'to_location_id' => $this->toLocation->id,
    ]);

    // Process the vendor bill stock move
    app(CreateJournalEntryForStockMoveAction::class)->execute($vendorBillStockMove, $this->user);

    // Verify vendor bill cost was used and tracked
    $vendorBillValuation = StockMoveValuation::where('stock_move_id', $vendorBillStockMove->id)->first();
    expect($vendorBillValuation)->not->toBeNull();
    expect($vendorBillValuation->cost_source)->toBe(CostSource::VendorBill);
    expect($vendorBillValuation->cost_source_reference)->toBe("VendorBill:{$vendorBill->id}");

    // Refresh product to see updated average cost
    $product->refresh();
    expect($product->average_cost)->toEqual(Money::of(50000, 'IQD'));

    // Step 3: Create manual stock move (should use vendor bill cost due to enhanced cost determination)
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
        description: 'Manual stock receipt using enhanced cost determination',
    );

    $manualStockMove = app(\Kezi\Inventory\Actions\Inventory\CreateStockMoveAction::class)->execute($dto);

    // Verify manual stock move used vendor bill cost (enhanced behavior)
    $manualValuation = StockMoveValuation::where('stock_move_id', $manualStockMove->id)->first();
    expect($manualValuation)->not->toBeNull();
    expect($manualValuation->cost_source)->toBe(CostSource::VendorBill);
    expect($manualValuation->cost_source_reference)->toBe("VendorBill:{$vendorBill->id}");
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
    expect($previewResult->getCostSource())->toBe(CostSource::VendorBill);
});

it('demonstrates fallback cost determination with warnings', function () {
    // Create product with only unit price (no average cost or cost layers)
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(0, 'IQD'),
        'unit_price' => Money::of(75000, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    // Test cost determination with fallbacks allowed
    $stockMove = StockMove::factory()->create([
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
    expect($validationResult->getSuggestedActions())->toContain('Create and post a vendor bill for this product to establish purchase cost');
});

it('demonstrates complete failure scenario with actionable suggestions', function () {
    // Create product without any cost information
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
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

    // Test that enhanced method throws detailed exception
    expect(fn () => $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced($product, $stockMove))
        ->toThrow(InsufficientCostInformationException::class);

    // Test validation service provides actionable suggestions
    $validationResult = $this->costValidationService->validateCostAvailability($product, StockMoveType::Incoming);
    expect($validationResult->isValid())->toBeFalse();
    expect($validationResult->getSuggestedActions())->toContain('Create and post a vendor bill for this product to establish purchase cost');
    expect($validationResult->getSuggestedActions())->toContain('Average cost is calculated automatically from posted vendor bills - no manual entry needed');

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

    expect(fn () => app(\Kezi\Inventory\Actions\Inventory\CreateStockMoveAction::class)->execute($dto))
        ->toThrow(InsufficientCostInformationException::class);
});

it('demonstrates FIFO cost layer fallback', function () {
    // Create FIFO product with cost layer but no average cost
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'average_cost' => Money::of(0, 'IQD'),
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $costLayer = InventoryCostLayer::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'cost_per_unit' => Money::of(45000, 'IQD'),
        'remaining_quantity' => 20.0,
    ]);

    $stockMove = StockMove::factory()->create([
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
