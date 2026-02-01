<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Accounting\Enums\Budgets\BudgetStatus;
use Kezi\Accounting\Exceptions\BudgetExceededException;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Budget;
use Kezi\Accounting\Models\BudgetLine;
use Kezi\Accounting\Models\Journal;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Services\PurchaseOrderService;
use Kezi\Purchase\Services\VendorBillService;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->currency = \Kezi\Foundation\Models\Currency::factory()->create(['code' => 'NZD']);
    $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
    $this->user = User::factory()->create();

    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    // Set team context BEFORE assigning permissions (required for company_id in model_has_permissions)
    setPermissionsTeamId($this->company->id);

    // Setup permissions
    $permissions = [
        'create_vendor_bill',
        'update_vendor_bill',
        'confirm_vendor_bill',
        'create_purchase_order',
        'update_purchase_order',
        'confirm_purchase_order',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $this->user->givePermissionTo($permission);
    }

    // Setup currency and accounts
    $this->currency = $this->company->currency;
    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '600000',
        'name' => 'Expense Account',
    ]);

    // Setup Default AP Account and Purchase Journal
    $this->apAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '200000',
        'name' => 'Accounts Payable',
        'type' => AccountType::Payable,
    ]);
    $this->company->update(['default_accounts_payable_id' => $this->apAccount->id]);

    $this->journal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Purchase,
        'short_code' => 'BILL',
    ]);
    $this->company->update(['default_purchase_journal_id' => $this->journal->id]);

    $this->taxAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '500000',
        'name' => 'Tax Account',
        'type' => AccountType::CurrentLiabilities,
    ]);
    $this->company->update([
        'default_tax_account_id' => $this->taxAccount->id,
        'default_tax_receivable_id' => $this->taxAccount->id,
    ]);
});

it('prevents posting a vendor bill that exceeds the budget', function () {
    $currencyCode = $this->currency->code;
    // Create a budget
    $budget = Budget::factory()->create([
        'company_id' => $this->company->id,
        'status' => BudgetStatus::Finalized,
        'period_start_date' => now()->startOfMonth(),
        'period_end_date' => now()->endOfMonth(),
    ]);

    BudgetLine::factory()->create([
        'company_id' => $this->company->id,
        'budget_id' => $budget->id,
        'account_id' => $this->expenseAccount->id,
        'budgeted_amount' => 1000,
    ]);

    // Create a draft vendor bill exceeding the budget
    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now(),
    ]);

    $product = \Kezi\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    \Kezi\Purchase\Models\VendorBillLine::factory()->create([
        'vendor_bill_id' => $bill->id,
        'product_id' => $product->id,
        'company_id' => $this->company->id,
        'expense_account_id' => $this->expenseAccount->id,
        'subtotal' => Money::of(1500, $currencyCode),
        'subtotal_company_currency' => Money::of(1500, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
        'unit_price' => Money::of(1500, $currencyCode),
        'tax_id' => null,
    ]);

    // Expect exception when posting
    $this->expectException(BudgetExceededException::class);

    app(VendorBillService::class)->post($bill->refresh(), $this->user);
});

it('allows posting a vendor bill within the budget', function () {
    $currencyCode = $this->currency->code;

    $budget = Budget::factory()->create([
        'company_id' => $this->company->id,
        'status' => BudgetStatus::Finalized,
        'period_start_date' => now()->startOfMonth(),
        'period_end_date' => now()->endOfMonth(),
    ]);

    BudgetLine::factory()->create([
        'company_id' => $this->company->id,
        'budget_id' => $budget->id,
        'account_id' => $this->expenseAccount->id,
        'budgeted_amount' => 1000,
    ]);

    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now(),
    ]);

    \Kezi\Purchase\Models\VendorBillLine::factory()->create([
        'vendor_bill_id' => $bill->id,
        'expense_account_id' => $this->expenseAccount->id,
        'subtotal' => Money::of(500, $currencyCode),
        'subtotal_company_currency' => Money::of(500, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
        'unit_price' => Money::of(500, $currencyCode),
        'tax_id' => null,
    ]);

    app(VendorBillService::class)->post($bill->refresh(), $this->user);

    expect($bill->refresh()->status)->toBe(VendorBillStatus::Posted);
});

it('prevents confirming a purchase order that exceeds the budget', function () {
    $currencyCode = $this->currency->code;

    $budget = Budget::factory()->create([
        'company_id' => $this->company->id,
        'status' => BudgetStatus::Finalized,
        'period_start_date' => now()->startOfMonth(),
        'period_end_date' => now()->endOfMonth(),
    ]);

    BudgetLine::factory()->create([
        'company_id' => $this->company->id,
        'budget_id' => $budget->id,
        'account_id' => $this->expenseAccount->id,
        'budgeted_amount' => 1000,
    ]);

    $product = \Kezi\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'status' => PurchaseOrderStatus::Draft,
        'po_date' => now(),
    ]);

    \Kezi\Purchase\Models\PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => Money::of(1500, $currencyCode),
        'unit_price_company_currency' => Money::of(1500, $currencyCode),
        'subtotal' => Money::of(1500, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
        'tax_id' => null,
    ]);

    // Ensure totals are calculated
    $po->calculateTotalsFromLines();
    $po->save();

    $this->expectException(BudgetExceededException::class);

    app(PurchaseOrderService::class)->confirm($po->refresh(), $this->user);
});

it('correctly calculates committed costs from POs and actuals from Bills', function () {
    $currencyCode = $this->currency->code;

    // Budget: 10,000
    $budget = Budget::factory()->create([
        'company_id' => $this->company->id,
        'status' => BudgetStatus::Finalized,
        'period_start_date' => now()->startOfMonth(),
        'period_end_date' => now()->endOfMonth(),
        'name' => 'Test Budget',
    ]);

    BudgetLine::factory()->create([
        'company_id' => $this->company->id,
        'budget_id' => $budget->id,
        'account_id' => $this->expenseAccount->id,
        'budgeted_amount' => Money::of(10000, $currencyCode),
    ]);

    $product = \Kezi\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'expense_account_id' => $this->expenseAccount->id,
        'name' => 'Test Product',
    ]);

    // 1. Confirm PO for 4000 -> Should pass.
    $po1 = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'status' => PurchaseOrderStatus::Draft,
        'po_date' => now(),
    ]);
    \Kezi\Purchase\Models\PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po1->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => Money::of(4000, $currencyCode),
        'unit_price_company_currency' => Money::of(4000, $currencyCode),
        'subtotal' => Money::of(4000, $currencyCode),
        'subtotal_company_currency' => Money::of(4000, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
        'tax_id' => null,
    ]);

    app(PurchaseOrderService::class)->confirm($po1->refresh(), $this->user);

    // 2. Try to confirm PO for 7000 -> Should fail (4000 + 7000 > 10000).
    $po2 = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'status' => PurchaseOrderStatus::Draft,
        'po_date' => now(),
    ]);
    \Kezi\Purchase\Models\PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po2->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => Money::of(7000, $currencyCode),
        'unit_price_company_currency' => Money::of(7000, $currencyCode),
        'subtotal' => Money::of(7000, $currencyCode),
        'subtotal_company_currency' => Money::of(7000, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
        'tax_id' => null,
    ]);

    try {
        app(PurchaseOrderService::class)->confirm($po2->refresh(), $this->user);
        $this->fail('Should have thrown BudgetExceededException');
    } catch (BudgetExceededException $e) {
        // Expected
    }

    // 3. Post Bill for PO1 (4000).
    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'purchase_order_id' => $po1->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now(),
        'accounting_date' => now(),
    ]);
    \Kezi\Purchase\Models\VendorBillLine::factory()->create([
        'vendor_bill_id' => $bill->id,
        'expense_account_id' => $this->expenseAccount->id,
        'subtotal' => Money::of(4000, $currencyCode),
        'subtotal_company_currency' => Money::of(4000, $currencyCode),
        'unit_price' => Money::of(4000, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
        'tax_id' => null,
    ]);

    app(VendorBillService::class)->post($bill->refresh(), $this->user);

    // 4. Try to confirm PO2 again -> Should still fail (4000 actual + 7000 try > 10000).
    try {
        app(PurchaseOrderService::class)->confirm($po2->refresh(), $this->user);
        $this->fail('Should have thrown BudgetExceededException');
    } catch (BudgetExceededException $e) {
        // Expected
    }

    // 5. Create valid PO3 for 3000 -> Should pass (4000 + 3000 = 7000 < 10000).
    $po3 = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'status' => PurchaseOrderStatus::Draft,
        'po_date' => now(),
    ]);
    \Kezi\Purchase\Models\PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po3->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => Money::of(3000, $currencyCode),
        'unit_price_company_currency' => Money::of(3000, $currencyCode),
        'subtotal' => Money::of(3000, $currencyCode),
        'subtotal_company_currency' => Money::of(3000, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
        'tax_id' => null,
    ]);

    app(PurchaseOrderService::class)->confirm($po3->refresh(), $this->user);

    expect($po3->refresh()->status)->toBe(PurchaseOrderStatus::ToReceive);
});
