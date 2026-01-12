<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryForInvoiceAction;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryForVendorBillAction;
use Modules\Accounting\Enums\Accounting\TaxType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Models\Currency;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceLine;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->currency = Currency::factory()->create(['code' => 'USD', 'symbol' => '$']);

    $this->company->update([
        'currency_id' => $this->currency->id,
        'default_accounts_receivable_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
        'default_accounts_payable_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
        'default_income_account_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
        'default_expense_account_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
        'default_tax_account_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
        'default_tax_receivable_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
        'default_sales_journal_id' => Journal::factory()->create(['company_id' => $this->company->id])->id,
        'default_purchase_journal_id' => Journal::factory()->create(['company_id' => $this->company->id])->id,
    ]);
});

test('it splits tax correctly for invoices with tax groups', function () {
    // 1. Setup Taxes
    $taxAccountA = Account::factory()->create(['company_id' => $this->company->id, 'name' => 'Tax A']);
    $taxAccountB = Account::factory()->create(['company_id' => $this->company->id, 'name' => 'Tax B']);

    $childTaxA = Tax::factory()->create([
        'company_id' => $this->company->id,
        'rate' => 10,
        'tax_account_id' => $taxAccountA->id,
        'name' => 'VAT 10%',
        'type' => TaxType::Sales,
    ]);

    $childTaxB = Tax::factory()->create([
        'company_id' => $this->company->id,
        'rate' => 5,
        'tax_account_id' => $taxAccountB->id,
        'name' => 'Reconstruction 5%',
        'type' => TaxType::Sales,
    ]);

    $groupTax = Tax::factory()->create([
        'company_id' => $this->company->id,
        'rate' => 15, // Sum of children
        'is_group' => true,
        'name' => 'Group Tax 15%',
        'type' => TaxType::Sales,
    ]);

    $groupTax->children()->attach([
        $childTaxA->id,
        $childTaxB->id,
    ]);

    // 2. Create Invoice
    // Subtotal 100. Tax should be 15. Split: 10 to A, 5 to B.
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
    ]);

    $line = InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'tax_id' => $groupTax->id,
        'quantity' => 1,
        'unit_price' => Money::of(100, 'USD'),
        'subtotal' => Money::of(100, 'USD'),
        'total_line_tax' => Money::of(15, 'USD'), // 15% of 100
    ]);

    // 3. Execute Action
    $action = app(CreateJournalEntryForInvoiceAction::class);
    $je = $action->execute($invoice, $this->user);

    // 4. Verification
    expect($je->lines)->toHaveCount(4); // 1 AR, 1 Income, 2 Taxes (Split)

    $taxLines = $je->lines->filter(function ($line) use ($taxAccountA, $taxAccountB) {
        return in_array($line->account_id, [$taxAccountA->id, $taxAccountB->id]);
    });

    expect($taxLines)->toHaveCount(2);

    $lineA = $taxLines->firstWhere('account_id', $taxAccountA->id);
    $lineB = $taxLines->firstWhere('account_id', $taxAccountB->id);

    // Amounts should be credit
    expect($lineA->credit->getAmount()->toFloat())->toBe(10.00); // 100 * 10/15 * 15/100? No, 15 * 10/15 = 10.
    expect($lineB->credit->getAmount()->toFloat())->toBe(5.00);
});

test('it handles mixed recoverability in vendor bills for tax groups', function () {
    // 1. Setup Taxes
    $taxAccountRecoverable = Account::factory()->create(['company_id' => $this->company->id, 'name' => 'Input VAT']);

    // Child 1: Recoverable (e.g. VAT 10%)
    $childRecoverable = Tax::factory()->create([
        'company_id' => $this->company->id,
        'rate' => 10,
        'tax_account_id' => $taxAccountRecoverable->id,
        'is_recoverable' => true,
        'type' => TaxType::Purchase,
    ]);

    // Child 2: Non-Recoverable (e.g. Duty 5%) -> Capitalized
    $childCapitalized = Tax::factory()->create([
        'company_id' => $this->company->id,
        'rate' => 5,
        'is_recoverable' => false,
        'type' => TaxType::Purchase,
    ]);

    $groupTax = Tax::factory()->create([
        'company_id' => $this->company->id,
        'rate' => 15,
        'is_group' => true,
        'type' => TaxType::Purchase,
    ]);

    $groupTax->children()->attach([$childRecoverable->id, $childCapitalized->id]);

    // 2. Create Vendor Bill
    // Subtotal 100. Total Tax 15.
    // Recoverable: 10. Capitalized: 5.
    // So Expense/Asset Cost should be 100 + 5 = 105.
    // Tax Line should be 10.
    // AP should be 115.

    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'vendor_id' => \Modules\Foundation\Models\Partner::factory()->create()->id,
        'total_amount' => Money::of(115, 'USD'),
    ]);

    // Create a generic expense line
    $expenseAccount = Account::factory()->create(['company_id' => $this->company->id]);

    $line = VendorBillLine::factory()->create([
        'vendor_bill_id' => $bill->id,
        'tax_id' => $groupTax->id,
        'expense_account_id' => $expenseAccount->id,
        'unit_price' => Money::of(100, 'USD'),
        'subtotal' => Money::of(100, 'USD'),
        'total_line_tax' => Money::of(15, 'USD'),
    ]);

    // 3. Execute
    $action = app(CreateJournalEntryForVendorBillAction::class);
    $je = $action->execute($bill, $this->user);

    // 4. Verification
    // Expect:
    // - 1 AP Line (115 Credit)
    // - 1 Expense Line (105 Debit = 100 + 5 Capitalized)
    // - 1 Tax Line (10 Debit = Recoverable)
    // Total lines: 3

    expect($je->lines)->toHaveCount(3);

    $expenseLine = $je->lines->firstWhere('account_id', $expenseAccount->id);
    expect($expenseLine->debit->getAmount()->toFloat())->toBe(105.00);
    expect($expenseLine->description)->toContain('(incl. capitalized tax)');

    $taxLine = $je->lines->firstWhere('account_id', $taxAccountRecoverable->id);
    expect($taxLine)->not->toBeNull();
    expect($taxLine->debit->getAmount()->toFloat())->toBe(10.00);
});
