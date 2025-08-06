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
        'move_type' => StockMoveType::INCOMING->value,
        'status' => StockMoveStatus::DONE->value,
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

<?php

namespace Tests\Feature\Inventory;

use Brick\Money\Money;
use App\Models\Partner;
use App\Models\Product;
use App\Models\VendorBill;
use App\Models\StockLocation;
use App\Enums\Products\ProductType;
use App\Services\VendorBillService;
use App\Enums\Inventory\StockMoveType;
use Tests\Traits\WithConfiguredCompany;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Inventory\StockLocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Purchases\CreateVendorBillLineAction;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

describe('Purchase to Stock Workflow (AVCO)', function () {
    /**
     * This beforeEach hook sets up the specific scenario for our inventory test.
     * It runs after the WithConfiguredCompany trait has already prepared the basic company structure.
     */
    beforeEach(function () {
        // We need to extend our configured company with inventory-specific accounts.
        // In a real implementation, these would be part of the CompanyBuilder.
        $this->inventoryAccount = \App\Models\Account::factory()->for($this->company)->create(['name' => 'Stock Valuation', 'type' => 'Asset']);
        $this->stockInputAccount = \App\Models\Account::factory()->for($this->company)->create(['name' => 'Stock Input', 'type' => 'Liability']);
        $this->company->update([
            'default_inventory_account_id' => $this->inventoryAccount->id,
            'default_stock_input_account_id' => $this->stockInputAccount->id,
        ]);

        // Create a storable product configured for AVCO valuation.
        $this->product = Product::factory()->for($this->company)->create([
            'type' => ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::AVCO,
            'default_inventory_account_id' => $this->inventoryAccount->id,
            'default_stock_input_account_id' => $this->stockInputAccount->id,
            'average_cost' => Money::of(0, $this->company->currency->code),
        ]);

        // Create the necessary physical locations.
        $this->vendorLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::VENDOR]);
        $this->stockLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::INTERNAL]);

        // Link these locations to the company for default usage.
        $this->company->vendorLocation()->associate($this->vendorLocation);
        $this->company->defaultStockLocation()->associate($this->stockLocation);
        $this->company->save();

        // Create a vendor.
        $this->vendor = Partner::factory()->for($this->company)->create(['type' => 'vendor']);
    });

    it('correctly processes an incoming storable product, creating a stock move and a balanced journal entry', function () {
        // Arrange
        $quantity = 10;
        $costPerUnit = Money::of(150, $this->company->currency->code); // e.g., 150.000 IQD
        $totalValue = $costPerUnit->multipliedBy($quantity);

        // Create a draft Vendor Bill for the product.
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'draft',
        ]);
        $lineDto = new CreateVendorBillLineDTO(
            vendorBill: $vendorBill,
            product_id: $this->product->id,
            quantity: $quantity,
            unit_price: $costPerUnit,
            expense_account_id: $this->product->expense_account_id
        );
        (new CreateVendorBillLineAction())->execute($lineDto);
        $vendorBill->refresh(); // Refresh to get totals calculated by observers.

        // Act: Confirm the vendor bill. This is the trigger for our entire workflow.
        $vendorBillService = resolve(VendorBillService::class);
        $vendorBillService->confirm($vendorBill, $this->user);

        // Assert
        $this->product->refresh();

        // 1. Assert Product's Average Cost: The product's cost should be updated.
        expect($this->product->average_cost->isEqualTo($costPerUnit))->toBeTrue();

        // 2. Assert Physical Stock Move: A record of the physical movement must exist and be 'done'.
        $this->assertDatabaseHas('stock_moves', [
            'product_id' => $this->product->id,
            'quantity' => $quantity,
            'from_location_id' => $this->vendorLocation->id,
            'to_location_id' => $this->stockLocation->id,
            'move_type' => StockMoveType::INCOMING->value,
            'status' => StockMoveStatus::DONE->value,
            'source_type' => VendorBill::class,
            'source_id' => $vendorBill->id,
        ]);

        // 3. Assert Accounting Impact (Journal Entry): An immutable journal entry must be created.
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => VendorBill::class,
            'source_id' => $vendorBill->id,
            'is_posted' => true,
            'total_debit' => $totalValue->getMinorAmount()->toInt(),
            'total_credit' => $totalValue->getMinorAmount()->toInt(),
        ]);

        $journalEntry = \App\Models\JournalEntry::latest('id')->first();

        // 4. Assert Journal Entry Lines: The entry must be balanced and hit the correct accounts.
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->inventoryAccount->id,
            'debit' => $totalValue->getMinorAmount()->toInt(),
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $this->stockInputAccount->id,
            'debit' => 0,
            'credit' => $totalValue->getMinorAmount()->toInt(),
        ]);
    });
});
