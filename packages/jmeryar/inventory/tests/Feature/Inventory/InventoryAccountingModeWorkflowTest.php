<?php

namespace Jmeryar\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Inventory\Actions\Inventory\ProcessIncomingStockAction;
use Jmeryar\Inventory\Enums\Inventory\InventoryAccountingMode;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockMoveType;
use Jmeryar\Inventory\Enums\Inventory\ValuationMethod;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Models\StockMoveValuation;
use Jmeryar\Inventory\Models\StockPicking;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Jmeryar\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Jmeryar\Purchase\Enums\Purchases\VendorBillStatus;
use Jmeryar\Purchase\Models\VendorBill;
use Jmeryar\Purchase\Services\VendorBillService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Create vendor
    $this->vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    // Create required accounts
    $this->inventoryAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => ['en' => 'Inventory Asset'],
        'type' => 'current_assets',
    ]);

    $this->stockInputAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => ['en' => 'Stock Input'],
        'type' => 'current_liabilities',
    ]);

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => ['en' => 'Expense Account'],
        'type' => 'expense',
    ]);

    // Create storable products
    $this->product1 = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product 1',
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    $this->product2 = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product 2',
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);
});

it('auto-records inventory when company mode is AUTO_RECORD_ON_BILL', function () {
    // Arrange: Set company to auto-record mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL,
    ]);

    // Create vendor bill with storable products
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
        'currency_id' => $this->company->currency_id,
    ]);

    // Add storable product lines
    $lineDto1 = new CreateVendorBillLineDTO(
        product_id: $this->product1->id,
        description: 'Product 1 Line',
        quantity: 5,
        unit_price: Money::of(100, $this->company->currency->code),
        expense_account_id: $this->product1->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto1);

    $lineDto2 = new CreateVendorBillLineDTO(
        product_id: $this->product2->id,
        description: 'Product 2 Line',
        quantity: 3,
        unit_price: Money::of(200, $this->company->currency->code),
        expense_account_id: $this->product2->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto2);

    $vendorBill->refresh();

    // Act: Post the vendor bill
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: Stock picking was created
    $stockPicking = StockPicking::where('origin', 'VendorBill#'.$vendorBill->getKey())->first();
    expect($stockPicking)->not->toBeNull();
    expect($stockPicking->state->value)->toBe('done');

    // Assert: Stock moves were created for BOTH products
    $stockMoves = StockMove::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->get();

    expect($stockMoves)->toHaveCount(1); // One stock move with multiple product lines

    $stockMove = $stockMoves->first();
    $productLines = $stockMove->productLines;
    expect($productLines)->toHaveCount(2);
    expect($productLines->pluck('product_id')->sort()->values()->toArray())
        ->toBe([$this->product1->id, $this->product2->id]);

    // Assert: Consolidated inventory journal entry was created
    $inventoryJournalEntry = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->where('reference', 'LIKE', 'STOCK-IN-%')
        ->first();
    expect($inventoryJournalEntry)->not->toBeNull();

    // Assert: StockMoveValuation records were created for BOTH products
    $stockMoveValuations = StockMoveValuation::whereIn('stock_move_id', $stockMoves->pluck('id'))->get();
    expect($stockMoveValuations)->toHaveCount(2);

    // Assert: Main vendor bill journal entry was also created
    $mainJournalEntry = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->where('reference', 'NOT LIKE', 'STOCK-IN-%')
        ->first();
    expect($mainJournalEntry)->not->toBeNull();

    // Total should be 2 journal entries: 1 main bill + 1 consolidated inventory
    $totalJournalEntries = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->count();
    expect($totalJournalEntries)->toBe(2);
});

it('does NOT auto-record inventory when company mode is MANUAL_INVENTORY_RECORDING', function () {
    // Arrange: Set company to manual recording mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
    ]);

    // Create vendor bill with storable products
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
        'currency_id' => $this->company->currency_id,
    ]);

    // Add storable product lines
    $lineDto1 = new CreateVendorBillLineDTO(
        product_id: $this->product1->id,
        description: 'Product 1 Line',
        quantity: 5,
        unit_price: Money::of(100, $this->company->currency->code),
        expense_account_id: $this->product1->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto1);

    $lineDto2 = new CreateVendorBillLineDTO(
        product_id: $this->product2->id,
        description: 'Product 2 Line',
        quantity: 3,
        unit_price: Money::of(200, $this->company->currency->code),
        expense_account_id: $this->product2->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto2);

    $vendorBill->refresh();

    // Act: Post the vendor bill
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: NO stock picking was created
    $stockPicking = StockPicking::where('origin', 'VendorBill#'.$vendorBill->getKey())->first();
    expect($stockPicking)->toBeNull();

    // Assert: NO stock moves were created
    $stockMoves = StockMove::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->get();
    expect($stockMoves)->toHaveCount(0);

    // Assert: NO inventory journal entry was created
    $inventoryJournalEntry = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->where('reference', 'LIKE', 'STOCK-IN-%')
        ->first();
    expect($inventoryJournalEntry)->toBeNull();

    // Assert: NO StockMoveValuation records were created
    $stockMoveValuations = StockMoveValuation::whereHas('stockMove', function ($query) use ($vendorBill) {
        $query->where('source_type', VendorBill::class)
            ->where('source_id', $vendorBill->id);
    })->get();
    expect($stockMoveValuations)->toHaveCount(0);

    // Assert: ONLY the main vendor bill journal entry was created
    $mainJournalEntry = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->first();
    expect($mainJournalEntry)->not->toBeNull();

    // Total should be 1 journal entry: only the main bill entry
    $totalJournalEntries = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->count();
    expect($totalJournalEntries)->toBe(1);
});

it('handles mixed product types correctly in manual mode', function () {
    // Arrange: Set company to manual recording mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
    ]);

    // Create a service product (non-storable)
    $serviceProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Service Product',
        'type' => \Jmeryar\Product\Enums\Products\ProductType::Service,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    // Create vendor bill with mixed product types
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
        'currency_id' => $this->company->currency_id,
    ]);

    // Add storable product line
    $storableLineDto = new CreateVendorBillLineDTO(
        product_id: $this->product1->id,
        description: 'Storable Product Line',
        quantity: 5,
        unit_price: Money::of(100, $this->company->currency->code),
        expense_account_id: $this->product1->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $storableLineDto);

    // Add service product line
    $serviceLineDto = new CreateVendorBillLineDTO(
        product_id: $serviceProduct->id,
        description: 'Service Product Line',
        quantity: 1,
        unit_price: Money::of(500, $this->company->currency->code),
        expense_account_id: $serviceProduct->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $serviceLineDto);

    $vendorBill->refresh();

    // Act: Post the vendor bill
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: NO stock moves were created (even though there are storable products)
    $stockMoves = StockMove::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->get();
    expect($stockMoves)->toHaveCount(0);

    // Assert: NO inventory journal entries were created
    $inventoryJournalEntry = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->where('reference', 'LIKE', 'STOCK-IN-%')
        ->first();
    expect($inventoryJournalEntry)->toBeNull();

    // Assert: ONLY the main vendor bill journal entry was created
    $totalJournalEntries = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->count();
    expect($totalJournalEntries)->toBe(1);

    // Assert: The main journal entry includes both storable and service products
    $mainJournalEntry = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->first();
    expect($mainJournalEntry)->not->toBeNull();

    // The journal entry should have lines for both products (in the main bill entry)
    $journalEntryLines = $mainJournalEntry->lines;
    expect($journalEntryLines->count())->toBeGreaterThan(0);
});

it('supports manual inventory receipt workflow after vendor bill posting', function () {
    // Arrange: Set company to manual recording mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
    ]);

    // Create vendor bill with storable products
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
        'currency_id' => $this->company->currency_id,
    ]);

    // Add storable product lines
    $lineDto1 = new CreateVendorBillLineDTO(
        product_id: $this->product1->id,
        description: 'Product 1 Line',
        quantity: 10, // Ordered 10 units
        unit_price: Money::of(100, $this->company->currency->code),
        expense_account_id: $this->product1->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto1);

    $lineDto2 = new CreateVendorBillLineDTO(
        product_id: $this->product2->id,
        description: 'Product 2 Line',
        quantity: 5, // Ordered 5 units
        unit_price: Money::of(200, $this->company->currency->code),
        expense_account_id: $this->product2->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto2);

    $vendorBill->refresh();

    // Step 1: Post the vendor bill (should NOT create inventory entries)
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Verify no inventory entries were created
    expect(StockMove::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->count())->toBe(0);

    expect(JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->where('reference', 'LIKE', 'STOCK-IN-%')
        ->count())->toBe(0);

    // Step 2: Simulate manual inventory receipt process
    // Warehouse staff physically receives goods and records them manually
    // Let's say they received only partial quantities:
    // - Product 1: Received 8 out of 10 ordered (2 units back-ordered)
    // - Product 2: Received 5 out of 5 ordered (complete)

    // Create manual stock moves for received items using factory for proper structure
    $stockMove1 = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product1->id,  // This will be handled by the factory
        'quantity' => 8,                      // This will be handled by the factory
        'from_location_id' => $this->company->vendorLocation->id,  // This will be handled by the factory
        'to_location_id' => $this->company->defaultStockLocation->id, // This will be handled by the factory
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'move_date' => now(),
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
        'reference' => 'MANUAL-RECEIPT-001',
        'created_by_user_id' => $this->user->id,
    ]);

    $stockMove2 = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $this->product2->id,  // This will be handled by the factory
        'quantity' => 5,                      // This will be handled by the factory
        'from_location_id' => $this->company->vendorLocation->id,  // This will be handled by the factory
        'to_location_id' => $this->company->defaultStockLocation->id, // This will be handled by the factory
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        'move_date' => now(),
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
        'reference' => 'MANUAL-RECEIPT-002',
        'created_by_user_id' => $this->user->id,
    ]);

    // Step 3: Process the manual stock moves to create inventory journal entries
    // This simulates the warehouse manager confirming the received quantities
    app(ProcessIncomingStockAction::class)->execute($stockMove1);
    app(ProcessIncomingStockAction::class)->execute($stockMove2);

    // Step 4: Verify that inventory journal entries were created for the manually received items
    // Note: When processing stock moves individually (as done here), each creates its own journal entry.
    // True consolidation happens when CreateStockMovesOnVendorBillConfirmed processes all moves together.
    $inventoryJournalEntries = JournalEntry::where('reference', 'LIKE', 'STOCK-IN-%')
        ->where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->get();

    expect($inventoryJournalEntries)->toHaveCount(2); // Each manually processed move creates its own entry

    // Verify StockMoveValuation records were created
    $stockMoveValuations = StockMoveValuation::whereIn('stock_move_id', [$stockMove1->id, $stockMove2->id])->get();
    expect($stockMoveValuations)->toHaveCount(2);

    // Verify the costs are calculated correctly based on vendor bill prices
    $valuation1 = $stockMoveValuations->where('stock_move_id', $stockMove1->id)->first();
    $valuation2 = $stockMoveValuations->where('stock_move_id', $stockMove2->id)->first();

    // Product 1: 8 units * 100 = 800
    $expectedCost1 = Money::of(800, $this->company->currency->code);
    // Product 2: 5 units * 200 = 1000
    $expectedCost2 = Money::of(1000, $this->company->currency->code);

    expect($valuation1->cost_impact->isEqualTo($expectedCost1))->toBeTrue();
    expect($valuation2->cost_impact->isEqualTo($expectedCost2))->toBeTrue();

    // Step 5: Verify total journal entries
    // Should have:
    // 1. Main vendor bill journal entry (created when bill was posted)
    // 2. Two inventory journal entries (one for each manually processed stock move)
    $totalJournalEntries = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->count();

    expect($totalJournalEntries)->toBe(3); // 1 main bill + 2 inventory entries

    // Step 6: Verify that only the actually received quantities are reflected in inventory
    // This demonstrates the key benefit of manual recording: precise control over what gets recorded
    expect((float) $stockMove1->productLines->first()->quantity)->toBe(8.0); // Not the full 10 ordered
    expect((float) $stockMove2->productLines->first()->quantity)->toBe(5.0); // Full quantity received

    // The remaining 2 units of Product 1 can be received later when they arrive
    // This flexibility is the main advantage of manual inventory recording mode
});
