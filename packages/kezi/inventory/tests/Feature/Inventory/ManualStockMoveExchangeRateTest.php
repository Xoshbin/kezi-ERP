<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryForVendorBillAction;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\AssetCategory;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Enums\Inventory\CostSource;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveProductLine;
use Kezi\Inventory\Services\Inventory\InventoryValuationService;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\CreateVendorBillAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();

    // Set up currencies
    $this->usdCurrency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD', 'decimal_places' => 2]);
    $this->iqdCurrency = Currency::where('code', 'IQD')->first() ?? Currency::factory()->create(['code' => 'IQD', 'decimal_places' => 3]);

    // Set company currency to IQD
    $this->company->update(['currency_id' => $this->iqdCurrency->id]);

    // Create vendor
    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);

    // Create locations
    $this->vendorLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Vendor Location',
        'type' => StockLocationType::Vendor,
    ]);
    $this->stockLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Stock Location',
        'type' => StockLocationType::Internal,
    ]);

    // Set company default locations
    $this->company->update([
        'default_vendor_location_id' => $this->vendorLocation->id,
        'default_stock_location_id' => $this->stockLocation->id,
    ]);

    // Create accounts first
    $assetAccount = Account::factory()->create(['company_id' => $this->company->id]);
    $depreciationAccount = Account::factory()->create(['company_id' => $this->company->id]);
    $this->expenseAccount = Account::factory()->create(['company_id' => $this->company->id]);

    // Create required default accounts for vendor bill processing
    $apAccount = Account::factory()->create(['company_id' => $this->company->id]);
    $purchaseJournal = Journal::factory()->create(['company_id' => $this->company->id]);

    // Set company default accounts
    $this->company->update([
        'default_accounts_payable_id' => $apAccount->id,
        'default_purchase_journal_id' => $purchaseJournal->id,
    ]);

    // Create asset category
    $this->assetCategory = AssetCategory::create([
        'company_id' => $this->company->id,
        'name' => 'Test Asset Category',
        'asset_account_id' => $assetAccount->id,
        'accumulated_depreciation_account_id' => $depreciationAccount->id,
        'depreciation_expense_account_id' => $this->expenseAccount->id,
        'depreciation_method' => DepreciationMethod::StraightLine,
        'useful_life_years' => 5,
        'is_active' => true,
    ]);

    // Create non-recoverable tax (5%)
    $this->tax = Tax::factory()->create([
        'company_id' => $this->company->id,
        'rate' => 5.0,
        'is_recoverable' => false,
    ]);

    // Create products
    $this->product1 = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
    ]);

    $this->product2 = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
    ]);

    $this->product3 = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
    ]);
});

it('manual stock move uses same exchange rate and cost calculation as vendor bill', function () {
    // Authenticate the user for the observer
    $this->actingAs($this->user);

    // Set company to manual inventory mode
    $this->company->update(['inventory_recording_mode' => 'manual']);

    // Create vendor bill in USD with specific exchange rate
    $exchangeRate = 1310.0;
    $unitPriceUsd = Money::of(19.00, 'USD'); // $19.00
    $quantity = 10;

    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        currency_id: $this->usdCurrency->id,
        bill_reference: 'TEST-BILL-001',
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: $this->product1->id,
                description: 'Test Product 1',
                quantity: $quantity,
                unit_price: $unitPriceUsd,
                expense_account_id: $this->expenseAccount->id,
                tax_id: $this->tax->id,
                analytic_account_id: null,
                asset_category_id: $this->assetCategory->id,
            ),
            new CreateVendorBillLineDTO(
                product_id: $this->product2->id,
                description: 'Test Product 2',
                quantity: $quantity,
                unit_price: $unitPriceUsd,
                expense_account_id: $this->expenseAccount->id,
                tax_id: $this->tax->id,
                analytic_account_id: null,
                asset_category_id: $this->assetCategory->id,
            ),
            new CreateVendorBillLineDTO(
                product_id: $this->product3->id,
                description: 'Test Product 3',
                quantity: $quantity,
                unit_price: $unitPriceUsd,
                expense_account_id: $this->expenseAccount->id,
                tax_id: $this->tax->id,
                analytic_account_id: null,
                asset_category_id: $this->assetCategory->id,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);

    // Set the exchange rate
    $vendorBill->update(['exchange_rate_at_creation' => $exchangeRate]);

    // Confirm the vendor bill to create journal entry
    $vendorBill->update(['status' => VendorBillStatus::Posted, 'posted_at' => now()]);

    // Manually create the journal entry for the vendor bill
    $createJournalEntryAction = app(CreateJournalEntryForVendorBillAction::class);
    $vendorBillJournalEntry = $createJournalEntryAction->execute($vendorBill, $this->user);
    $vendorBillTotalAmount = $vendorBillJournalEntry->total_debit;

    // Create manual stock move (not linked to vendor bill - truly manual)
    $stockMove = StockMove::create([
        'company_id' => $this->company->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Draft,
        'move_date' => now(),
        'reference' => 'MANUAL-MOVE-001',
        'description' => 'Manual stock receipt',
        'created_by_user_id' => $this->user->id,
        // No source_type or source_id - this is a truly manual stock move
    ]);

    // Create product lines for all three products
    StockMoveProductLine::create([
        'stock_move_id' => $stockMove->id,
        'company_id' => $this->company->id,
        'product_id' => $this->product1->id,
        'quantity' => $quantity,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
        'description' => 'Manual receipt - Product 1',
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $stockMove->id,
        'company_id' => $this->company->id,
        'product_id' => $this->product2->id,
        'quantity' => $quantity,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
        'description' => 'Manual receipt - Product 2',
    ]);

    StockMoveProductLine::create([
        'stock_move_id' => $stockMove->id,
        'company_id' => $this->company->id,
        'product_id' => $this->product3->id,
        'quantity' => $quantity,
        'from_location_id' => $this->vendorLocation->id,
        'to_location_id' => $this->stockLocation->id,
        'description' => 'Manual receipt - Product 3',
    ]);

    // Confirm the stock move to trigger journal entry creation
    $stockMove->update(['status' => StockMoveStatus::Done]);

    // Get the consolidated stock move journal entry
    $stockMoveValuations = $stockMove->stockMoveValuations;
    expect($stockMoveValuations)->toHaveCount(3, 'Should have 3 stock move valuations for 3 products');

    // All valuations should point to the same consolidated journal entry
    $firstJournalEntry = $stockMoveValuations->first()->journalEntry;
    expect($firstJournalEntry)->not->toBeNull('Stock move journal entry should exist');

    // Verify all valuations use the same journal entry (consolidated)
    foreach ($stockMoveValuations as $valuation) {
        expect($valuation->journalEntry->id)->toEqual($firstJournalEntry->id, 'All valuations should use the same consolidated journal entry');
    }

    $stockMoveTotalAmount = $firstJournalEntry->total_debit;

    // The journal entry amounts should match exactly
    expect($stockMoveTotalAmount)->toEqual($vendorBillTotalAmount)
        ->and($firstJournalEntry->currency_id)->toEqual($this->iqdCurrency->id)
        ->and($vendorBillJournalEntry->currency_id)->toEqual($this->iqdCurrency->id);

    // Verify the cost calculation includes the exchange rate and capitalized tax
    $inventoryValuationService = app(InventoryValuationService::class);
    $costResult = $inventoryValuationService->calculateIncomingCostPerUnitEnhanced(
        $this->product1,
        $stockMove,
        false
    );

    // Expected cost calculation:
    // Unit price: $19.00 * 1310 = 24,890 IQD
    // Tax per unit: ($19.00 * 0.05) * 1310 = 1,244.5 IQD
    // Total cost per unit: 24,890 + 1,244.5 = 26,134.5 IQD
    $expectedCostPerUnit = Money::of(26134.5, 'IQD');

    expect($costResult->cost->isEqualTo($expectedCostPerUnit))->toBeTrue()
        ->and($costResult->source)->toBe(CostSource::VendorBill)
        ->and($costResult->reference)->toContain("VendorBill:{$vendorBill->id}");
});
