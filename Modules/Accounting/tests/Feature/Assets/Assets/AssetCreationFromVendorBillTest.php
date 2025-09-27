<?php

namespace Modules\Accounting\Tests\Feature\Accounting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Partner;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    // Arrange: The WithConfiguredCompany trait provides $this->company and $this->user.
    // We only need to create data specific to this test's context.
    $this->vendor = Partner::factory()->for($this->company)->vendor()->create();

    // Create an account that IS flagged to create assets
    $this->assetAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'IT Equipment'],
        'type' => 'fixed_assets',
        'can_create_assets' => true,
    ]);

    // Create a standard expense account that IS NOT flagged
    $this->expenseAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Office Supplies'],
        'type' => 'expense',
        'can_create_assets' => false,
    ]);

    // Create depreciation-related default accounts and attach to company instance (not persisted columns)
    $this->depreciationExpenseAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Depreciation Expense'],
        'type' => 'depreciation',
    ]);
    $this->accumulatedDepreciationAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Accumulated Depreciation'],
        'type' => 'non_current_assets',
    ]);
    $this->company->setAttribute('default_depreciation_expense_account_id', $this->depreciationExpenseAccount->id);
    $this->company->setAttribute('default_accumulated_depreciation_account_id', $this->accumulatedDepreciationAccount->id);

});

test('it creates an asset when a vendor bill line uses an asset creation account', function () {
    // Arrange: Prepare the DTO with data from our configured environment.
    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        currency_id: $this->company->currency_id,
        bill_reference: 'ASSET-BILL-001',
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: null,
                description: 'New Server Rack',
                quantity: 1,
                unit_price: 150, // Major units for IQD (150.000 -> 150000 minor)
                expense_account_id: $this->assetAccount->id,
                tax_id: null,
                analytic_account_id: null,
            ),
            new CreateVendorBillLineDTO(
                product_id: null,
                description: 'Pens and Paper',
                quantity: 5,
                unit_price: 1000,
                expense_account_id: $this->expenseAccount->id,
                tax_id: null,
                analytic_account_id: null,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    // Act: Create and then post the vendor bill.
    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: The asset was created and the bill was posted.
    $this->assertDatabaseCount('assets', 1);
    $this->assertDatabaseHas('assets', [
        'name' => 'New Server Rack',
        'purchase_value' => 150000,
        'asset_account_id' => $this->assetAccount->id,
        'source_id' => $vendorBill->id,
        'source_type' => get_class($vendorBill),
    ]);

    $this->assertDatabaseHas('vendor_bills', [
        'id' => $vendorBill->id,
        'status' => VendorBillStatus::Posted->value,
    ]);
});

test('it does not create an asset when no bill lines use an asset creation account', function () {
    // Arrange: Prepare a DTO with only standard expense lines.
    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        currency_id: $this->company->currency_id,
        bill_reference: 'EXPENSE-BILL-002',
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: null,
                description: 'Catering for meeting',
                quantity: 1,
                unit_price: 25000,
                expense_account_id: $this->expenseAccount->id,
                tax_id: null,
                analytic_account_id: null,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    // Act: Create and then post the vendor bill.
    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: No asset was created, but the bill was still posted.
    $this->assertDatabaseCount('assets', 0);

    expect($vendorBill->refresh()->status)->toBe(VendorBillStatus::Posted);
});
