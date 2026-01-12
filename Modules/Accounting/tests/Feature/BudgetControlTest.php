<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Enums\Budgets\BudgetStatus;
use Modules\Accounting\Exceptions\BudgetExceededException;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetLine;
use Modules\Accounting\Models\Journal;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Purchase\Services\VendorBillService;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();

    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

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
});

it('prevents posting a vendor bill that exceeds the budget', function () {
    $currencyCode = $this->company->currency->code;
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
        'status' => VendorBillStatus::Draft,
        'bill_date' => now(),
    ]);

    \Modules\Purchase\Models\VendorBillLine::factory()->create([
        'vendor_bill_id' => $bill->id,
        'expense_account_id' => $this->expenseAccount->id,
        'subtotal' => Money::of(1500, $currencyCode),
        'subtotal_company_currency' => Money::of(1500, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
    ]);

    // Expect exception when posting
    $this->expectException(BudgetExceededException::class);

    app(VendorBillService::class)->post($bill, $this->user);
});

it('allows posting a vendor bill within the budget', function () {
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
        'status' => VendorBillStatus::Draft,
        'bill_date' => now(),
    ]);

    \Modules\Purchase\Models\VendorBillLine::factory()->create([
        'vendor_bill_id' => $bill->id,
        'expense_account_id' => $this->expenseAccount->id,
        'subtotal' => 500,
    ]);

    app(VendorBillService::class)->post($bill, $this->user);

    expect($bill->refresh()->status)->toBe(VendorBillStatus::Posted);
});

it('prevents confirming a purchase order that exceeds the budget', function () {
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

    $product = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => PurchaseOrderStatus::Draft,
        'po_date' => now(),
    ]);

    \Modules\Purchase\Models\PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => Money::of(1500, $this->currency->code),
    ]);

    // Ensure totals are calculated
    $po->calculateTotalsFromLines();
    $po->save();

    $this->expectException(BudgetExceededException::class);

    app(PurchaseOrderService::class)->confirm($po, $this->user);
});

it('correctly calculates committed costs from POs and actuals from Bills', function () {
    $currencyCode = $this->currency->code;

    // Budget: 1000
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
        'budgeted_amount' => 1000, // Stored as minor units or Money? BudgetLineFactory uses numberBetween (integer). Cast handles it.
    ]);

    $product = \Modules\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    // 1. Confirm PO for 600 -> Should pass. Committed: 600, Actual: 0. Available: 400.
    $po1 = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => PurchaseOrderStatus::Draft,
        'po_date' => now(),
    ]);
    \Modules\Purchase\Models\PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po1->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => Money::of(400, $currencyCode),
        'unit_price_company_currency' => Money::of(400, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
        // 'total_company_currency' => calculated automatically or must set if not calculated in factory?
        // Factory usually calculates it? Or we can let it be null and calculateTotalsFromLines?
    ]);
    $po1->calculateTotalsFromLines();
    $po1->save();

    app(PurchaseOrderService::class)->confirm($po1, $this->user);

    // 2. Try to confirm PO for 1500 -> Should fail (400 + 1500 > 1000).
    $po2 = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => PurchaseOrderStatus::Draft,
        'po_date' => now(),
    ]);
    \Modules\Purchase\Models\PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po2->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => Money::of(1500, $currencyCode),
        'unit_price_company_currency' => Money::of(1500, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
    ]);
    $po2->calculateTotalsFromLines();
    $po2->save();

    try {
        app(PurchaseOrderService::class)->confirm($po2, $this->user);
        $this->fail('Should have thrown BudgetExceededException');
    } catch (BudgetExceededException $e) {
        // Expected
    }

    // 3. Post Bill for PO1 (400).
    // Creates Vendor Bill linked to PO1.
    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'purchase_order_id' => $po1->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now(),
    ]);
    \Modules\Purchase\Models\VendorBillLine::factory()->create([
        'vendor_bill_id' => $bill->id,
        'expense_account_id' => $this->expenseAccount->id,
        'subtotal' => Money::of(400, $currencyCode),
        'subtotal_company_currency' => Money::of(400, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
    ]);

    app(VendorBillService::class)->post($bill, $this->user);

    // Total Usage = 400. Available = 600.

    // 4. Try to confirm PO2 (500) again -> Should still fail (600 + 500 > 1000).
    try {
        app(PurchaseOrderService::class)->confirm($po2, $this->user);
        $this->fail('Should have thrown BudgetExceededException');
    } catch (BudgetExceededException $e) {
        // Expected
    }

    // 5. Create valid PO3 for 300 -> Should pass (600 + 300 = 900 < 1000).
    $po3 = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => PurchaseOrderStatus::Draft,
        'po_date' => now(),
    ]);
    \Modules\Purchase\Models\PurchaseOrderLine::factory()->create([
        'purchase_order_id' => $po3->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => Money::of(300, $currencyCode),
        'unit_price_company_currency' => Money::of(300, $currencyCode),
    ]);
    $po3->calculateTotalsFromLines();
    $po3->save();

    app(PurchaseOrderService::class)->confirm($po3, $this->user);

    expect($po3->status)->toBe(PurchaseOrderStatus::Confirmed);
});
