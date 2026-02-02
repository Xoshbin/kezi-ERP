<?php

namespace Kezi\Inventory\Tests\Feature\Inventory;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Services\VendorBillService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();      // Standard setup for company, user, etc.
    $this->setupInventoryTestEnvironment(); // Specialized setup for inventory tests.

    // The test-specific product creation remains here, which is correct.
    $this->product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);
});

it('correctly processes an incoming storable product, creating a stock move and a balanced journal entry', function () {
    // Arrange
    $quantity = 10;
    $costPerUnit = Money::of(150, $this->company->currency->code);
    $totalValue = $costPerUnit->multipliedBy($quantity);

    // Create a draft Vendor Bill for the product.
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => 'draft',
    ]);
    $lineDto = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Test Product',
        quantity: $quantity,
        unit_price: '150',
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null
    );
    resolve(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);
    $vendorBill->refresh(); // Refresh to get totals calculated by observers.

    // Act: Post the vendor bill. This is the single trigger for our workflow.
    $vendorBillService = resolve(VendorBillService::class);
    $vendorBillService->post($vendorBill, $this->user);

    // Assert
    $this->product->refresh();

    // 1. Assert Product's Average Cost
    expect($this->product->average_cost->isEqualTo($costPerUnit))->toBeTrue();

    // 2. Assert Physical Stock Move
    $this->assertDatabaseHas('stock_moves', [
        'move_type' => StockMoveType::Incoming->value,
        'status' => StockMoveStatus::Done->value,
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
    ]);

    // 3. Assert Product Line was created with correct details
    $this->assertDatabaseHas('stock_move_product_lines', [
        'product_id' => $this->product->id,
        'quantity' => $quantity,
        'from_location_id' => $this->company->vendorLocation->id,
        'to_location_id' => $this->company->defaultStockLocation->id,
    ]);

    // 3. Assert Accounting Impact (Journal Entry)
    $journalEntry = $vendorBill->journalEntry;
    $this->assertNotNull($journalEntry);
    expect($journalEntry->is_posted)->toBeTrue();

    // 4. Assert Journal Entry Lines
    // Vendor Bill JE: Dr Stock Input, Cr Accounts Payable (Anglo-Saxon)
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->product->default_stock_input_account_id,
        'debit' => $totalValue->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_accounts_payable_id,
        'debit' => 0,
        'credit' => $totalValue->getMinorAmount()->toInt(),
    ]);

    // Separate Valuation JE: Dr Inventory, Cr Stock Input
    $valuationReference = 'STOCK-IN-VendorBill-'.$vendorBill->id;
    $valuationEntry = JournalEntry::where('reference', $valuationReference)->first();
    expect($valuationEntry)->not->toBeNull();

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $valuationEntry->id,
        'account_id' => $this->product->default_inventory_account_id,
        'debit' => $totalValue->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $valuationEntry->id,
        'account_id' => $this->product->default_stock_input_account_id,
        'debit' => 0,
        'credit' => $totalValue->getMinorAmount()->toInt(),
    ]);

    // Valuation link exists
    $this->assertDatabaseHas('stock_move_valuations', [
        'product_id' => $this->product->id,
        'move_type' => StockMoveType::Incoming->value,
        'journal_entry_id' => $valuationEntry->id,
    ]);
});
