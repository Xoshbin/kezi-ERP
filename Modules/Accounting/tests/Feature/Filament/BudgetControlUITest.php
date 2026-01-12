<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Enums\Budgets\BudgetStatus;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Budget;
use Modules\Accounting\Models\BudgetLine;
use Modules\Accounting\Models\Journal;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\VendorBill;
use Spatie\Permission\Models\Permission;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

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
    $currencyCode = $this->currency->code;

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '600000',
        'name' => 'Expense Account',
    ]);

    // Ensure Default AP Account and Purchase Journal are set
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

    // Create a budget
    $this->budget = Budget::factory()->create([
        'company_id' => $this->company->id,
        'status' => BudgetStatus::Finalized,
        'period_start_date' => now()->startOfMonth(),
        'period_end_date' => now()->endOfMonth(),
    ]);

    BudgetLine::factory()->create([
        'company_id' => $this->company->id,
        'budget_id' => $this->budget->id,
        'account_id' => $this->expenseAccount->id,
        'budgeted_amount' => 1000,
    ]);
});

it('shows error notification when posting vendor bill exceeding budget', function () {
    $currencyCode = $this->currency->code;

    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now(),
        'accounting_date' => now(),
    ]);

    \Modules\Purchase\Models\VendorBillLine::factory()->create([
        'vendor_bill_id' => $bill->id,
        'expense_account_id' => $this->expenseAccount->id,
        'subtotal' => Money::of(1500, $currencyCode),
        'subtotal_company_currency' => Money::of(1500, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
        'tax_id' => null,
    ]);

    $bill->calculateTotalsFromLines();
    $bill->save();
    $bill->refresh();

    livewire(EditVendorBill::class, ['record' => $bill->id])
        ->assertActionExists('post')
        ->assertActionVisible('post')
        ->callAction('post')
        ->assertNotified();
});

it('shows error notification when confirming purchase order exceeding budget', function () {
    $currencyCode = $this->currency->code;

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
        'unit_price' => Money::of(1500, $currencyCode),
        'unit_price_company_currency' => Money::of(1500, $currencyCode),
        'subtotal' => Money::of(1500, $currencyCode),
        'subtotal_company_currency' => Money::of(1500, $currencyCode),
        'total_line_tax' => Money::of(0, $currencyCode),
        'tax_id' => null,
    ]);

    $po->calculateTotalsFromLines();
    $po->save();
    $po->refresh();

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertActionExists('confirm')
        ->assertActionVisible('confirm')
        ->callAction('confirm')
        ->assertNotified();
});
