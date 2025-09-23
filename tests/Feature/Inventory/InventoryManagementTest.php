<?php

namespace Tests\Feature\Inventory;

use App\Actions\Purchases\CreateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Models\Product;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
});

it('correctly processes an incoming storable product using AVCO, creating a stock move and balanced journal entry', function () {
    // Arrange
    $purchaseDate = Carbon::create(2025, 8, 15);
    Carbon::setTestNow($purchaseDate);

    $quantity = 10;
    $costPerUnit = Money::of(150, 'IQD');
    $totalValue = $costPerUnit->multipliedBy($quantity);

    $product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    // Act
    $lineDto = new CreateVendorBillLineDTO(
        product_id: $product->id,
        description: 'Test Product',
        quantity: $quantity,
        unit_price: '150',
        expense_account_id: $product->expense_account_id,
        tax_id: null,
        analytic_account_id: null
    );
    $billDto = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        bill_reference: 'TEST-001',
        currency_id: $this->company->currency_id,
        bill_date: $purchaseDate->toDateString(),
        accounting_date: $purchaseDate->toDateString(),
        due_date: $purchaseDate->addDays(30)->toDateString(),
        lines: [$lineDto],
        created_by_user_id: $this->user->id
    );

    $vendorBill = resolve(CreateVendorBillAction::class)->execute($billDto);
    resolve(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert
    $vendorBill->refresh();
    $product->refresh();

    // 1. Assert Product's Average Cost
    expect($product->average_cost->isEqualTo($costPerUnit))->toBeTrue();

    // 2. Assert Physical Stock Move
    $this->assertDatabaseHas('stock_moves', [
        'move_type' => StockMoveType::Incoming->value,
        'status' => StockMoveStatus::Done->value,
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
    ]);

    // 3. Assert Product Line was created with correct details
    $this->assertDatabaseHas('stock_move_product_lines', [
        'product_id' => $product->id,
        'quantity' => $quantity,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
    ]);

    // 3. Assert Journal Entry and Lines
    $this->assertNotNull($vendorBill->journal_entry_id);
    $journalEntry = $vendorBill->journalEntry;

    // Phase 1: Vendor Bill JE debits Stock Input (valuation JE handles Inventory Dr)
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->stockInputAccount->id,
        'debit' => $totalValue->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_accounts_payable_id,
        'debit' => 0,
        'credit' => $totalValue->getMinorAmount()->toInt(),
    ]);
});
