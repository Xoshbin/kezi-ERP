<?php

use App\Models\Company;
use App\Models\Partner;
use App\Models\VendorBill;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Account;
use App\Models\Currency;
use App\Services\VendorBillService;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Actions\Purchases\CreateVendorBillAction;
use Tests\Traits\WithConfiguredCompany;
use Brick\Money\Money;
use Illuminate\Support\Facades\Event;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->actingAs($this->user);
});

test('confirming an inter-company vendor bill creates a corresponding invoice in the linked company', function () {
    // ARRANGE: Set up the world for our test.

    // 1. Create the company hierarchy with proper configuration
    $parentCompany = \Tests\Builders\CompanyBuilder::new()
        ->withDefaultAccounts()
        ->withDefaultJournals()
        ->withDefaultStockLocations()
        ->create();
    $parentCompany->update(['name' => 'ParentCo']);

    $childCompany = \Tests\Builders\CompanyBuilder::new()
        ->withDefaultAccounts()
        ->withDefaultJournals()
        ->withDefaultStockLocations()
        ->create();
    $childCompany->update([
        'name' => 'ChildCo',
        'parent_company_id' => $parentCompany->id,
    ]);

    // 2. Create corresponding partner records for each company.
    // In ChildCo's books, ParentCo is a "Vendor".
    $parentCompanyVendor = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'ParentCo (Vendor)',
        'type' => 'vendor',
        'linked_company_id' => $parentCompany->id,
    ]);

    // In ParentCo's books, ChildCo is a "Customer".
    $childCompanyCustomer = Partner::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'ChildCo (Customer)',
        'type' => 'customer',
        'linked_company_id' => $childCompany->id,
    ]);

    // 3. Create inventory accounts for both companies
    $childInventoryAccount = Account::factory()->create([
        'company_id' => $childCompany->id,
        'type' => 'current_assets',
        'name' => ['en' => 'Inventory Account'],
    ]);

    $childStockInputAccount = Account::factory()->create([
        'company_id' => $childCompany->id,
        'type' => 'current_liabilities',
        'name' => ['en' => 'Stock Input Account'],
    ]);

    // 4. Create a product that exists in both companies
    $product = Product::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Test Product',
        'type' => 'service', // Use service type to avoid inventory complications
    ]);

    // Create corresponding product in parent company
    $parentProduct = Product::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'Test Product',
        'type' => 'service',
    ]);

    // 5. Create expense account for the vendor bill
    $expenseAccount = Account::factory()->create([
        'company_id' => $childCompany->id,
        'type' => 'expense',
        'name' => ['en' => 'Test Expense Account'],
    ]);

    // 6. Create income account for the invoice
    $incomeAccount = Account::factory()->create([
        'company_id' => $parentCompany->id,
        'type' => 'income',
        'name' => ['en' => 'Test Income Account'],
    ]);

    // Update product to have the income account
    $parentProduct->update(['income_account_id' => $incomeAccount->id]);

    // 7. Create a vendor bill in the child company
    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $childCompany->id,
        vendor_id: $parentCompanyVendor->id,
        currency_id: $this->company->currency->id,
        bill_reference: 'BILL-001',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: $product->id,
                description: 'Test Service',
                quantity: 1,
                unit_price: Money::of(1000, $this->company->currency->code),
                expense_account_id: $expenseAccount->id,
                tax_id: null,
                analytic_account_id: null,
            )
        ],
        created_by_user_id: $this->user->id,
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);

    // ACT: Perform the business logic we want to test.
    app(VendorBillService::class)->confirm($vendorBill, $this->user);

    // ASSERT: Verify the expected outcomes.

    // Assert an Invoice now exists in the PARENT company's books...
    $this->assertDatabaseHas('invoices', [
        'company_id' => $parentCompany->id,
        'customer_id' => $childCompanyCustomer->id,
        'currency_id' => $this->company->currency->id,
        'reference' => "IC-BILL-{$vendorBill->id}", // Audit trail through reference
    ]);

    // Get the created invoice
    $invoice = Invoice::where('company_id', $parentCompany->id)
        ->where('reference', "IC-BILL-{$vendorBill->id}")
        ->first();

    expect($invoice)->not->toBeNull();
    expect($invoice->status->value)->toBe('posted'); // Should be automatically posted
    expect($invoice->total_amount)->toEqual($vendorBill->total_amount);

    // Verify the invoice line was created correctly
    expect($invoice->invoiceLines)->toHaveCount(1);
    $invoiceLine = $invoice->invoiceLines->first();
    expect($invoiceLine->description)->toBe('Test Service');
    expect($invoiceLine->quantity)->toBe('1.00');
    expect($invoiceLine->unit_price)->toEqual(Money::of(1000, $this->company->currency->code));
});

test('inter-company vendor bill creation only triggers for vendors with linked companies', function () {
    // Create a regular vendor (no linked company)
    $regularVendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Regular Vendor',
        'type' => 'vendor',
        'linked_company_id' => null,
    ]);

    // Create expense account
    $expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'expense',
        'name' => ['en' => 'Test Expense Account'],
    ]);

    // Create a vendor bill with regular vendor
    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $regularVendor->id,
        currency_id: $this->company->currency->id,
        bill_reference: 'BILL-002',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: null,
                description: 'Regular Service',
                quantity: 1,
                unit_price: Money::of(500, $this->company->currency->code),
                expense_account_id: $expenseAccount->id,
                tax_id: null,
                analytic_account_id: null,
            )
        ],
        created_by_user_id: $this->user->id,
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);

    // Confirm the vendor bill
    app(VendorBillService::class)->confirm($vendorBill, $this->user);

    // Assert NO invoice was created (since this is not an inter-company transaction)
    $this->assertDatabaseMissing('invoices', [
        'reference' => "IC-BILL-{$vendorBill->id}",
    ]);
});

test('inter-company vendor bill does not create invoice in same company', function () {
    // Create a vendor linked to the same company (should not trigger inter-company)
    $sameCompanyVendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Same Company Vendor',
        'type' => 'vendor',
        'linked_company_id' => $this->company->id, // Same company
    ]);

    // Create expense account
    $expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'expense',
        'name' => ['en' => 'Test Expense Account'],
    ]);

    // Create a vendor bill
    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $sameCompanyVendor->id,
        currency_id: $this->company->currency->id,
        bill_reference: 'BILL-003',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: null,
                description: 'Same Company Service',
                quantity: 1,
                unit_price: Money::of(300, $this->company->currency->code),
                expense_account_id: $expenseAccount->id,
                tax_id: null,
                analytic_account_id: null,
            )
        ],
        created_by_user_id: $this->user->id,
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);

    // Confirm the vendor bill
    app(VendorBillService::class)->confirm($vendorBill, $this->user);

    // Assert NO invoice was created (since this is the same company)
    $this->assertDatabaseMissing('invoices', [
        'reference' => "IC-BILL-{$vendorBill->id}",
    ]);
});
