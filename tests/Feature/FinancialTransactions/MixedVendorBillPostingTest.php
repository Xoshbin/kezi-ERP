<?php

namespace Tests\Feature\FinancialTransactions;

use App\Actions\Purchases\CreateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Enums\Products\ProductType;
use App\Models\Account;
use App\Models\AssetCategory;
use App\Models\Product;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('posts a single JE for mixed vendor bills (storable + asset + expense)', function () {
    $this->setupWithConfiguredCompany();
    $this->vendor = \App\Models\Partner::factory()->for($this->company)->vendor()->create();

    // Set up accounts and product
    $inventoryAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Inventory'], 'type' => 'current_assets',
    ]);
    $stockInputAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Stock Input'], 'type' => 'current_liabilities',
    ]);
    $expenseAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Office Supplies'], 'type' => 'expense',
    ]);

    $product = Product::factory()->for($this->company)->create([
        'name' => 'Widget',
        'type' => ProductType::Storable,
        'default_inventory_account_id' => $inventoryAccount->id,
        'default_stock_input_account_id' => $stockInputAccount->id,
    ]);

    $assetAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'IT Equipment'], 'type' => 'fixed_assets',
    ]);
    $accumDepAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Accum Dep'], 'type' => 'non_current_assets',
    ]);
    $depExpenseAccount = Account::factory()->for($this->company)->create([
        'name' => ['en' => 'Depr Expense'], 'type' => 'depreciation',
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

    // Build vendor bill DTO with three lines
    $lines = [
        new CreateVendorBillLineDTO(
            product_id: $product->id,
            description: 'Storable items',
            quantity: 2,
            unit_price: Money::of(100, $this->company->currency->code),
            expense_account_id: $expenseAccount->id, // unused for storable
            tax_id: null,
            analytic_account_id: null,
        ),
        new CreateVendorBillLineDTO(
            product_id: null,
            description: 'Asset Laptop',
            quantity: 1,
            unit_price: Money::of(1200, $this->company->currency->code),
            expense_account_id: $assetAccount->id,
            tax_id: null,
            analytic_account_id: null,
            asset_category_id: $category->id,
        ),
        new CreateVendorBillLineDTO(
            product_id: null,
            description: 'Office paper',
            quantity: 5,
            unit_price: Money::of(10, $this->company->currency->code),
            expense_account_id: $expenseAccount->id,
            tax_id: null,
            analytic_account_id: null,
        ),
    ];

    $bill = app(CreateVendorBillAction::class)->execute(new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->vendor->id,
        currency_id: $this->company->currency_id,
        bill_reference: 'MIX-001',
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: $lines,
        created_by_user_id: $this->user->id,
    ));

    app(VendorBillService::class)->post($bill, $this->user);

    $entry = $bill->refresh()->journalEntry;
    expect($entry)->not->toBeNull();

    $subtotalStorable = Money::of(100, $this->company->currency->code)->multipliedBy(2);
    $assetAmount = Money::of(1200, $this->company->currency->code);
    $expenseAmount = Money::of(10, $this->company->currency->code)->multipliedBy(5);
    $apTotal = $subtotalStorable->plus($assetAmount)->plus($expenseAmount)->getMinorAmount()->toInt();

    // Inventory debit exists
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $entry->id,
        'account_id' => $inventoryAccount->id,
        'debit' => $subtotalStorable->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    // Asset debit exists
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $entry->id,
        'account_id' => $assetAccount->id,
        'debit' => $assetAmount->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    // Expense debit exists
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $entry->id,
        'account_id' => $expenseAccount->id,
        'debit' => $expenseAmount->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    // Single AP credit for sum
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $entry->id,
        'account_id' => $this->company->default_accounts_payable_id,
        'debit' => 0,
        'credit' => $apTotal,
    ]);
});

