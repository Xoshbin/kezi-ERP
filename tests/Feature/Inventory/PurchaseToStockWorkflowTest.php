<?php

namespace Tests\Feature\Inventory;

use App\Actions\Purchases\CreateVendorBillLineAction;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Enums\Inventory\StockLocationType;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Models\Partner;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();      // Standard setup for company, user, etc.
    $this->setupInventoryTestEnvironment(); // Specialized setup for inventory tests.

    // The test-specific product creation remains here, which is correct.
    $this->product = \App\Models\Product::factory()->for($this->company)->create([
        'type' => \App\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => \App\Enums\Inventory\ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'average_cost' => \Brick\Money\Money::of(0, $this->company->currency->code),
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
        'product_id' => $this->product->id,
        'quantity' => $quantity,
        'from_location_id' => $this->company->vendorLocation->id,
        'to_location_id' => $this->company->defaultStockLocation->id,
        'move_type' => StockMoveType::Incoming->value,
        'status' => StockMoveStatus::Done->value,
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
    ]);

    // 3. Assert Accounting Impact (Journal Entry)
    $journalEntry = $vendorBill->journalEntry;
    $this->assertNotNull($journalEntry);
    expect($journalEntry->is_posted)->toBeTrue();

    // 4. Assert Journal Entry Lines
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->product->default_inventory_account_id,
        'debit' => $totalValue->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->product->default_stock_input_account_id,
        'debit' => 0,
        'credit' => $totalValue->getMinorAmount()->toInt(),
    ]);
});
