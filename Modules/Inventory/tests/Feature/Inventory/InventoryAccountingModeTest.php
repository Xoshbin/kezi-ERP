<?php

namespace Modules\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use App\Models\Company;
use Modules\Product\Models\Product;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Partner;
use Modules\Inventory\Models\StockMove;
use Modules\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;
use Modules\Accounting\Models\JournalEntry;
use Modules\Product\Enums\Products\ProductType;
use Modules\Inventory\Models\StockMoveValuation;
use Modules\Purchase\Services\VendorBillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Inventory\Enums\Inventory\InventoryAccountingMode;
use Modules\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;

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

    // Create storable product
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product',
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);
});

it('creates inventory journal entries when company uses AUTO_RECORD_ON_BILL mode', function () {
    // Arrange: Set company to auto-record mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL,
    ]);

    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
    ]);

    $lineDto = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Test Product Line',
        quantity: 5,
        unit_price: Money::of(100, $this->company->currency->code),
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

    $vendorBill->refresh();

    // Act: Post the vendor bill
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: Stock move was created
    $stockMove = StockMove::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->first();

    expect($stockMove)->not->toBeNull();

    // Check product line
    $productLine = $stockMove->productLines()->where('product_id', $this->product->id)->first();
    expect($productLine)->not->toBeNull();
    expect((float) $productLine->quantity)->toBe(5.0);

    // Assert: Inventory journal entry was created (consolidated)
    $inventoryJournalEntry = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->where('reference', 'LIKE', 'STOCK-IN-%')
        ->first();
    expect($inventoryJournalEntry)->not->toBeNull();

    // Assert: StockMoveValuation was created
    $stockMoveValuation = StockMoveValuation::where('stock_move_id', $stockMove->id)->first();
    expect($stockMoveValuation)->not->toBeNull();
    expect($stockMoveValuation->journal_entry_id)->toBe($inventoryJournalEntry->id);

    // Assert: Journal entry has correct amounts
    $expectedCost = Money::of(100, $this->company->currency->code)->multipliedBy(5);
    expect($stockMoveValuation->cost_impact->isEqualTo($expectedCost))->toBeTrue();

    // Assert: Journal entry lines exist
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $inventoryJournalEntry->id,
        'account_id' => $this->inventoryAccount->id,
        'debit' => $expectedCost->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $inventoryJournalEntry->id,
        'account_id' => $this->stockInputAccount->id,
        'debit' => 0,
        'credit' => $expectedCost->getMinorAmount()->toInt(),
    ]);
});

it('does NOT create inventory journal entries when company uses MANUAL_INVENTORY_RECORDING mode', function () {
    // Arrange: Set company to manual recording mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
    ]);

    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
    ]);

    $lineDto = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Test Product Line',
        quantity: 5,
        unit_price: Money::of(100, $this->company->currency->code),
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

    $vendorBill->refresh();

    // Act: Post the vendor bill
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: NO stock moves were created
    $stockMoves = StockMove::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->get();

    expect($stockMoves)->toHaveCount(0);

    // Assert: NO inventory journal entries were created
    $stockMoveValuations = StockMoveValuation::whereIn('stock_move_id', $stockMoves->pluck('id'))->get();
    expect($stockMoveValuations)->toHaveCount(0);

    // Assert: Only the main vendor bill journal entry exists
    $mainJournalEntry = $vendorBill->refresh()->journalEntry;
    expect($mainJournalEntry)->not->toBeNull();

    $allJournalEntries = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->get();

    expect($allJournalEntries)->toHaveCount(1); // Only the main bill JE, no inventory JEs
});

it('creates inventory journal entries for ALL storable products in AUTO_RECORD_ON_BILL mode', function () {
    // Arrange: Set company to auto-record mode
    $this->company->update([
        'inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL,
    ]);

    // Create second product
    $product2 = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product 2',
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->format('Y-m-d'),
        'accounting_date' => now()->format('Y-m-d'),
    ]);

    // Create lines for both products
    $lineDto1 = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Product 1 Line',
        quantity: 3,
        unit_price: Money::of(100, $this->company->currency->code),
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto1);

    $lineDto2 = new CreateVendorBillLineDTO(
        product_id: $product2->id,
        description: 'Product 2 Line',
        quantity: 2,
        unit_price: Money::of(200, $this->company->currency->code),
        expense_account_id: $product2->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto2);

    $vendorBill->refresh();

    // Act: Post the vendor bill
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: One stock move was created with BOTH product lines
    $stockMove = StockMove::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->first();

    expect($stockMove)->not->toBeNull();

    // Check product lines
    $productLines = $stockMove->productLines;
    expect($productLines)->toHaveCount(2);

    $productLine1 = $productLines->where('product_id', $this->product->id)->first();
    $productLine2 = $productLines->where('product_id', $product2->id)->first();

    expect($productLine1)->not->toBeNull();
    expect($productLine2)->not->toBeNull();
    expect((float) $productLine1->quantity)->toBe(3.0);
    expect((float) $productLine2->quantity)->toBe(2.0);

    // Assert: One consolidated inventory journal entry was created for BOTH products
    $inventoryJournalEntry = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->where('reference', 'LIKE', 'STOCK-IN-%')
        ->first();
    expect($inventoryJournalEntry)->not->toBeNull();

    // Assert: StockMoveValuation records were created for BOTH products
    $stockMoveValuations = StockMoveValuation::where('stock_move_id', $stockMove->id)->get();
    expect($stockMoveValuations)->toHaveCount(2);

    $valuation1 = $stockMoveValuations->where('product_id', $this->product->id)->first();
    $valuation2 = $stockMoveValuations->where('product_id', $product2->id)->first();

    expect($valuation1)->not->toBeNull();
    expect($valuation2)->not->toBeNull();

    // Both valuations should reference the same consolidated journal entry
    expect($valuation1->journal_entry_id)->toBe($inventoryJournalEntry->id);
    expect($valuation2->journal_entry_id)->toBe($inventoryJournalEntry->id);

    // Assert: Correct amounts
    $expectedCost1 = Money::of(100, $this->company->currency->code)->multipliedBy(3);
    $expectedCost2 = Money::of(200, $this->company->currency->code)->multipliedBy(2);

    expect($valuation1->cost_impact->isEqualTo($expectedCost1))->toBeTrue();
    expect($valuation2->cost_impact->isEqualTo($expectedCost2))->toBeTrue();

    // Assert: Consolidated journal entry has lines for both products
    $journalEntryLines = $inventoryJournalEntry->lines;
    expect($journalEntryLines)->toHaveCount(4); // 2 debit lines + 2 credit lines

    // Assert: Total journal entries = 1 main bill JE + 1 consolidated inventory JE
    $allJournalEntries = JournalEntry::where('source_type', VendorBill::class)
        ->where('source_id', $vendorBill->id)
        ->get();

    expect($allJournalEntries)->toHaveCount(2);
});

it('has correct default inventory accounting mode for new companies', function () {
    // Assert: Default mode is AUTO_RECORD_ON_BILL
    expect(InventoryAccountingMode::getDefault())->toBe(InventoryAccountingMode::AUTO_RECORD_ON_BILL);

    // Assert: New company gets the default mode
    $newCompany = Company::factory()->create();
    expect($newCompany->inventory_accounting_mode)->toBe(InventoryAccountingMode::AUTO_RECORD_ON_BILL);
});
