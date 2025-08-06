<?php

namespace Tests\Feature\Inventory;

use App\Actions\Purchases\CreateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Enums\Inventory\StockLocationType;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\ValuationMethod;
use App\Enums\Partners\PartnerType;
use App\Enums\Products\ProductType;
use App\Models\JournalEntry;
use App\Models\Partner;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use App\Models\User;
use App\Models\Currency;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('correctly processes an incoming storable product using AVCO, creating a stock move and balanced journal entry', function () {
    // Arrange: Set up the world for our test
    // The WithConfiguredCompany trait has already run, so $this->company is available.
    $purchaseDate = Carbon::create(2025, 8, 15);
    Carbon::setTestNow($purchaseDate);

    $quantity = 10;
    // Using Brick/Money for precision as per our architecture
    $costPerUnit = Money::of(15000, 'IQD');
    $totalValue = $costPerUnit->multipliedBy($quantity);

    // 1. Define the necessary GL Accounts from our configured company
    $inventoryAccount = $this->company->default_inventory_account_id;
    $stockInputAccount = $this->company->default_stock_input_account_id;

    // 2. Create a storable product configured for AVCO valuation
    $product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $inventoryAccount,
        'default_stock_input_account_id' => $stockInputAccount,
    ]);

    // 3. Create a vendor and the physical stock locations
    $vendor = Partner::factory()->for($this->company)->create(['type' => PartnerType::Vendor]);
    $vendorLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::VENDOR]);
    $stockLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::INTERNAL]);
    $user = User::factory()->for($this->company)->create();

    // Act: Execute the business logic by following the established architectural pattern.

    // 1. Create the DTO for the vendor bill lines.
    $vendorBillLines = [
        new CreateVendorBillLineDTO(
            description: 'High-End Laptop for Business Use',
            quantity: 1,
            product_id: null,
            tax_id: null,
            analytic_account_id: null,
            unit_price: $costPerUnit,
        )
    ];

    // 2. Create the main DTO for the vendor bill.
    $dto = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $vendor->id,
        bill_reference: 'KE-LAPTOP-001',
        currency_id: $this->company->currency_id,
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: $vendorBillLines
    );

    // 3. Instantiate and execute the Action to create the draft document.
    $createVendorBillAction = new CreateVendorBillAction();
    $vendorBill = $createVendorBillAction->execute($dto);

    // 4. Instantiate and execute the Service to post the document, triggering inventory logic.
    $vendorBillService = resolve(VendorBillService::class); // Use resolve() to get an instance with dependencies
    $vendorBillService->post($vendorBill, $user);


    // Assert: Verify the results are correct
    $product->refresh();

    // 1. Assert the product's average cost is updated correctly.
    expect($product->average_cost->isEqualTo($costPerUnit))->toBeTrue();

    // 2. Assert the physical stock move was created correctly.
    $this->assertDatabaseHas('stock_moves', [
        'product_id' => $product->id,
        'quantity' => $quantity,
        'from_location_id' => $vendorLocation->id,
        'to_location_id' => $stockLocation->id,
        'move_type' => StockMoveType::INCOMING->value,
        'status' => 'done',
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
    ]);

    // 3. Assert the financial valuation record was created.
    $this->assertDatabaseHas('stock_move_valuations', [
        'product_id' => $product->id,
        'quantity' => $quantity,
        'cost_impact_currency_id' => 'IQD',
        'cost_impact_amount' => $totalValue->getAmount()->toInt(),
        'valuation_method' => ValuationMethod::AVCO->value,
    ]);

    // 4. Assert the double-entry journal entry is correct and immutable.
    $journalEntry = JournalEntry::latest('id')->first();
    $this->assertDatabaseHas('journal_entries', [
        'id' => $journalEntry->id,
        'journal_id' => $this->company->default_purchase_journal_id,
        'date' => $purchaseDate->toDateString(),
        'state' => 'posted',
        'previous_hash' => fn($value) => $value !== null,
    ]);

    // 5. Assert the journal entry lines are balanced.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $inventoryAccount,
        'debit' => $totalValue->getAmount()->toInt(),
        'credit' => 0,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $stockInputAccount,
        'debit' => 0,
        'credit' => $totalValue->getAmount()->toInt(),
    ]);
});
