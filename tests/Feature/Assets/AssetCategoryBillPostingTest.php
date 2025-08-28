<?php

namespace Tests\Feature\Assets;

use App\Actions\Purchases\CreateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Models\Account;
use App\Models\AssetCategory;
use App\Models\VendorBill;
use App\Models\Partner;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('creates asset and posts Dr Asset / Cr AP for asset category bill lines', function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = Partner::factory()->for($this->company)->vendor()->create();

    // Accounts for the asset category
    $assetAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Office Equipment'],
        'type' => 'fixed_assets',
    ]);
    $accumDepAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Accumulated Depreciation'],
        'type' => 'non_current_assets',
    ]);
    $depExpenseAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Depreciation Expense'],
        'type' => 'depreciation',
    ]);

    $category = AssetCategory::create([
        'company_id' => $this->company->id,
        'name' => 'IT Equipment',
        'asset_account_id' => $assetAccount->id,
        'accumulated_depreciation_account_id' => $accumDepAccount->id,
        'depreciation_expense_account_id' => $depExpenseAccount->id,
        'depreciation_method' => \App\Enums\Assets\DepreciationMethod::StraightLine,
        'useful_life_years' => 5,
        'salvage_value_default' => 0,
    ]);

    $vendorBillDTO = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        currency_id: $this->company->currency_id,
        bill_reference: 'ASSET-CAT-001',
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: null,
                description: 'Laptop',
                quantity: 1,
                unit_price: Money::of(1200, $this->company->currency->code),
                expense_account_id: $assetAccount->id,
                tax_id: null,
                analytic_account_id: null,
                asset_category_id: $category->id,
            ),
        ],
        created_by_user_id: $this->user->id,
    );

    $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);
    app(VendorBillService::class)->post($vendorBill, $this->user);

    $journalEntry = $vendorBill->refresh()->journalEntry;
    expect($journalEntry)->not->toBeNull();

    $amount = Money::of(1200, $this->company->currency->code)->getMinorAmount()->toInt();

    // Dr Asset
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $assetAccount->id,
        'debit' => $amount,
        'credit' => 0,
    ]);

    // Cr AP
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_accounts_payable_id,
        'debit' => 0,
        'credit' => $amount,
    ]);

    // Asset record created and linked to bill
    $this->assertDatabaseHas('assets', [
        'company_id' => $this->company->id,
        'name' => 'Laptop',
        'purchase_value' => $amount,
        'asset_account_id' => $assetAccount->id,
        'accumulated_depreciation_account_id' => $accumDepAccount->id,
        'depreciation_expense_account_id' => $depExpenseAccount->id,
        'source_type' => VendorBill::class,
        'source_id' => $vendorBill->id,
    ]);
});

