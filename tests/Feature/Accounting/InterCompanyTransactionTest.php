<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use App\Models\Account;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\VendorBill;
use App\Services\InvoiceService;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalType;
use Tests\TestCase; // Pest uses the standard Laravel TestCase

// The 'test' function is the Pest-specific way to define a test case.
test('confirming an inter-company invoice creates a corresponding vendor bill in the parent company', function () {
    // ARRANGE: Set up the world for our test.

    // 1. Create the company hierarchy
    $parentCompany = Company::factory()->create(['name' => 'ParentCo']);
    $childCompany = Company::factory()->create([
        'name' => 'ChildCo',
        'parent_company_id' => $parentCompany->id,
    ]);

    // 2. Create corresponding partner records for each company.
    // In ParentCo's books, ChildCo is a "Vendor".
    $childCompanyPartner = Partner::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'ChildCo (Vendor)',
        'linked_company_id' => $childCompany->id,
    ]);

    // In ChildCo's books, ParentCo is a "Customer".
    $partnerForChild = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'ParentCo (Customer)',
        'linked_company_id' => $parentCompany->id,
    ]);

    $arAccount = Account::factory()->for($childCompany)->create(['type' => AccountType::Receivable]);
    $salesJournal = Journal::factory()->for($childCompany)->create(['type' => JournalType::Sale]);

    // Create purchase journal for parent company (required for vendor bill posting)
    $purchaseJournal = Journal::factory()->for($parentCompany)->create(['type' => JournalType::Purchase]);

    // Create expense account for products
    $expenseAccount = Account::factory()->for($parentCompany)->create(['type' => AccountType::Expense]);

    // Create stock locations for parent company (required for vendor bill posting)
    $parentVendorLocation = \App\Models\StockLocation::factory()->for($parentCompany)->create(['name' => 'Vendor Location']);
    $parentStockLocation = \App\Models\StockLocation::factory()->for($parentCompany)->create(['name' => 'Stock Location']);

    $childCompany->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_sales_journal_id' => $salesJournal->id,
    ]);

    $parentCompany->update([
        'default_vendor_location_id' => $parentVendorLocation->id,
        'default_stock_location_id' => $parentStockLocation->id,
        'default_purchase_journal_id' => $purchaseJournal->id,
    ]);

    $user = User::factory()->create(['company_id' => $childCompany->id]);
    $this->actingAs($user);

    // Create required accounts for the product
    $inventoryAccount = Account::factory()->for($parentCompany)->create(['type' => AccountType::CurrentAssets]);
    $stockInputAccount = Account::factory()->for($parentCompany)->create(['type' => AccountType::CurrentAssets]);

    // Create a product with all required accounts for the vendor bill creation
    $product = \App\Models\Product::factory()->for($childCompany)->create([
        'expense_account_id' => $expenseAccount->id,
        'default_inventory_account_id' => $inventoryAccount->id,
        'default_stock_input_account_id' => $stockInputAccount->id,
    ]);

    // 3. Create the source invoice in the Child Company for the Parent Company.
    $invoice = Invoice::factory()->for($childCompany)->create([
        'customer_id' => $partnerForChild->id,
        'total_amount' => 1000000, // 1,000.000 IQD in minor units
        'invoice_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
    ]);

    // Create invoice line with the product
    \App\Models\InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'description' => 'Test Product',
        'quantity' => 1,
        'unit_price' => \Brick\Money\Money::of(1000000, 'IQD'),
        'subtotal' => \Brick\Money\Money::of(1000000, 'IQD'),
        'total_line_tax' => \Brick\Money\Money::of(0, 'IQD'),
        'income_account_id' => $arAccount->id,
    ]);

    // ACT: Perform the business logic we want to test.
    app(InvoiceService::class)->confirm($invoice, $user);

    // ASSERT: Verify the expected outcomes.

    // Assert a VendorBill now exists in the PARENT company's books...
    $this->assertDatabaseHas('vendor_bills', [
        'company_id' => $parentCompany->id,
        'vendor_id' => $childCompanyPartner->id,
        'total_amount' => 1000000000, // IQD has 3 decimal places, so 1000000 IQD = 1000000000 minor units
        'bill_reference' => "IC-INV-{$invoice->id}", // Audit trail through reference
    ]);

    // Assert the new bill's Journal Entry was also created in the PARENT company's ledger.
    $vendorBill = VendorBill::where('bill_reference', "IC-INV-{$invoice->id}")->firstOrFail();

    // Check that the vendor bill has a journal entry associated with it
    $this->assertNotNull($vendorBill->journal_entry_id);

    // Check that a journal entry exists in the parent company
    $this->assertDatabaseHas('journal_entries', [
        'company_id' => $parentCompany->id,
        'id' => $vendorBill->journal_entry_id,
    ]);
});
