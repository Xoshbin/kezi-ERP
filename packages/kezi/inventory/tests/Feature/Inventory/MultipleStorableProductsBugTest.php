<?php

namespace Kezi\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveValuation;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Services\VendorBillService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Create vendor
    $this->vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    // Create required accounts
    $this->inventoryAccount1 = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => ['en' => 'Inventory Asset 1'],
        'type' => 'current_assets',
    ]);

    $this->inventoryAccount2 = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => ['en' => 'Inventory Asset 2'],
        'type' => 'current_assets',
    ]);

    $this->stockInputAccount1 = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => ['en' => 'Stock Input 1'],
        'type' => 'current_liabilities',
    ]);

    $this->stockInputAccount2 = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => ['en' => 'Stock Input 2'],
        'type' => 'current_liabilities',
    ]);

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => ['en' => 'Expense Account'],
        'type' => 'expense',
    ]);

    // Create two different storable products
    $this->product1 = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Product 1',
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::Avco,
        'default_inventory_account_id' => $this->inventoryAccount1->id,
        'default_stock_input_account_id' => $this->stockInputAccount1->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    $this->product2 = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Product 2',
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::Avco,
        'default_inventory_account_id' => $this->inventoryAccount2->id,
        'default_stock_input_account_id' => $this->stockInputAccount2->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);
});

it('reproduces the bug: creates inventory journal entries for ALL storable products, not just the first one', function () {
    // Arrange: Create vendor bill with TWO storable products
    $quantity1 = 5;
    $unitPrice1 = Money::of(100, $this->company->currency->code);
    $quantity2 = 3;
    $unitPrice2 = Money::of(200, $this->company->currency->code);

    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
    ]);

    // Create line for Product 1
    $lineDto1 = new CreateVendorBillLineDTO(
        product_id: $this->product1->id,
        description: 'Product 1 Line',
        quantity: $quantity1,
        unit_price: $unitPrice1,
        expense_account_id: $this->product1->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto1);

    // Create line for Product 2
    $lineDto2 = new CreateVendorBillLineDTO(
        product_id: $this->product2->id,
        description: 'Product 2 Line',
        quantity: $quantity2,
        unit_price: $unitPrice2,
        expense_account_id: $this->product2->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto2);

    $vendorBill->refresh();

    // Act: Post the vendor bill
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: Verify stock move was created with product lines for BOTH products
    $stockMoves = StockMove::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->get();

    expect($stockMoves)->toHaveCount(1); // One stock move with multiple product lines

    $stockMove = $stockMoves->first();
    $productLines = $stockMove->productLines;
    expect($productLines)->toHaveCount(2);

    $productLine1 = $productLines->where('product_id', $this->product1->id)->first();
    $productLine2 = $productLines->where('product_id', $this->product2->id)->first();

    expect($productLine1)->not->toBeNull();
    expect($productLine2)->not->toBeNull();
    expect((float) $productLine1->quantity)->toBe((float) $quantity1);
    expect((float) $productLine2->quantity)->toBe((float) $quantity2);

    // Assert: Verify inventory journal entries were created for BOTH products
    $stockMoveValuations = StockMoveValuation::whereIn('stock_move_id', $stockMoves->pluck('id'))->get();

    expect($stockMoveValuations)->toHaveCount(2);

    $valuation1 = $stockMoveValuations->where('product_id', $this->product1->id)->first();
    $valuation2 = $stockMoveValuations->where('product_id', $this->product2->id)->first();

    expect($valuation1)->not->toBeNull();
    expect($valuation2)->not->toBeNull();

    // Assert: Verify one consolidated inventory journal entry exists for both products
    $inventoryJournalEntries = JournalEntry::whereIn('id', $stockMoveValuations->pluck('journal_entry_id'))->get();

    expect($inventoryJournalEntries)->toHaveCount(1, 'Should have 1 consolidated inventory journal entry');

    // Both valuations should reference the same consolidated journal entry
    expect($valuation1->journal_entry_id)->toBe($valuation2->journal_entry_id);

    // Assert: Verify the journal entries have correct amounts
    $expectedCost1 = $unitPrice1->multipliedBy($quantity1);
    $expectedCost2 = $unitPrice2->multipliedBy($quantity2);

    expect($valuation1->cost_impact->isEqualTo($expectedCost1))->toBeTrue();
    expect($valuation2->cost_impact->isEqualTo($expectedCost2))->toBeTrue();

    // Assert: Verify journal entry lines exist for both products in the consolidated entry
    $consolidatedJournalEntry = $inventoryJournalEntries->first();

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $consolidatedJournalEntry->id,
        'account_id' => $this->inventoryAccount1->id,
        'debit' => $expectedCost1->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $consolidatedJournalEntry->id,
        'account_id' => $this->inventoryAccount2->id,
        'debit' => $expectedCost2->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);
});

it('verifies that each storable product gets its own inventory valuation journal entry with unique references', function () {
    // This test specifically checks for unique journal entry references to avoid constraint violations

    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_reference' => 'MULTI-TEST-001',
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
    ]);

    // Add multiple lines with same product to test reference uniqueness
    $lineDto1 = new CreateVendorBillLineDTO(
        product_id: $this->product1->id,
        description: 'Product 1 Line A',
        quantity: 2,
        unit_price: Money::of(50, $this->company->currency->code),
        expense_account_id: $this->product1->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto1);

    $lineDto2 = new CreateVendorBillLineDTO(
        product_id: $this->product2->id,
        description: 'Product 2 Line B',
        quantity: 1,
        unit_price: Money::of(300, $this->company->currency->code),
        expense_account_id: $this->product2->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto2);

    $vendorBill->refresh();

    // Act: Post the vendor bill
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: Verify all journal entries have unique references
    $allJournalEntries = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->get();

    $references = $allJournalEntries->pluck('reference')->toArray();
    $uniqueReferences = array_unique($references);

    expect(count($references))->toBe(count($uniqueReferences))
        ->and($allJournalEntries)->toHaveCount(2); // 1 main bill JE + 1 consolidated inventory JE
});
