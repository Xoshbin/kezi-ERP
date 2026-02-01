<?php

namespace Jmeryar\Accounting\Tests\Feature\Services\Reports;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use InvalidArgumentException;
use Jmeryar\Accounting\Enums\Accounting\JournalType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Accounting\Models\JournalEntryLine;
use Jmeryar\Foundation\Models\Partner;

beforeEach(function () {
    $this->company = Company::factory()->create();
});

test('it generates a partner ledger for a customer with invoices and payments', function () {
    // Arrange
    $currency = $this->company->currency->code;

    // Create partner with dedicated receivable/payable accounts
    $receivableAccount = Account::factory()->for($this->company)->create(['type' => 'receivable']);
    $payableAccount = Account::factory()->for($this->company)->create(['type' => 'payable']);
    $partner = Partner::factory()->for($this->company)->create([
        'receivable_account_id' => $receivableAccount->id,
        'payable_account_id' => $payableAccount->id,
    ]);

    $salesJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Sale]);
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $otherAccount = Account::factory()->for($this->company)->create(['type' => 'income']);

    $startDate = Carbon::parse('2025-03-01');
    $endDate = Carbon::parse('2025-03-31');

    // Transaction 1: Old invoice to establish an opening balance (1,000,000 IQD)
    $entry1 = JournalEntry::factory()->for($this->company)->for($salesJournal)
        ->create(['entry_date' => '2025-02-15', 'state' => 'posted', 'reference' => 'INV/OLD/001', 'currency_id' => $this->company->currency_id]);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry1->id, 'account_id' => $receivableAccount->id, 'debit' => '1000000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry1->id, 'account_id' => $otherAccount->id, 'debit' => '0', 'credit' => '1000000']);

    // Transaction 2: New invoice within the period (500,000 IQD)
    $entry2 = JournalEntry::factory()->for($this->company)->for($salesJournal)
        ->create(['entry_date' => '2025-03-10', 'state' => 'posted', 'reference' => 'INV/NEW/001', 'currency_id' => $this->company->currency_id]);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry2->id, 'account_id' => $receivableAccount->id, 'debit' => '500000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry2->id, 'account_id' => $otherAccount->id, 'debit' => '0', 'credit' => '500000']);

    // Transaction 3: Payment received within the period (700,000 IQD)
    $entry3 = JournalEntry::factory()->for($this->company)->for($bankJournal)
        ->create(['entry_date' => '2025-03-20', 'state' => 'posted', 'reference' => 'PMT/001', 'currency_id' => $this->company->currency_id]);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry3->id, 'account_id' => $receivableAccount->id, 'debit' => '0', 'credit' => '700000']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry3->id, 'account_id' => $otherAccount->id, 'debit' => '700000', 'credit' => '0']);

    // Action
    $service = app(\Jmeryar\Accounting\Services\Reports\PartnerLedgerService::class);
    $report = $service->generate($this->company, $partner, $startDate, $endDate);

    // Assert
    // 1. Check Opening Balance (from old invoice)
    $expectedOpeningBalance = Money::of('1000000', $currency);
    expect($report->openingBalance)->toEqual($expectedOpeningBalance);

    // 2. Check Transactions
    expect($report->transactionLines)->toHaveCount(2);

    // Line 1: New Invoice
    $line1 = $report->transactionLines[0];
    expect($line1->transactionType)->toBe('Invoice');
    expect($line1->reference)->toBe('INV/NEW/001');
    expect($line1->debit)->toEqual(Money::of('500000', $currency));
    expect($line1->balance)->toEqual(Money::of('1500000', $currency)); // 1M + 500k

    // Line 2: Payment
    $line2 = $report->transactionLines[1];
    expect($line2->transactionType)->toBe('Payment');
    expect($line2->reference)->toBe('PMT/001');
    expect($line2->credit)->toEqual(Money::of('700000', $currency));
    expect($line2->balance)->toEqual(Money::of('800000', $currency)); // 1.5M - 700k

    // 3. Check Closing Balance
    $expectedClosingBalance = Money::of('800000', $currency);
    expect($report->closingBalance)->toEqual($expectedClosingBalance);

    // 4. Check Partner Details
    expect($report->partnerId)->toBe($partner->id);
    expect($report->partnerName)->toBe($partner->name);
    expect($report->currency)->toBe($currency);
});

test('it throws exception when partner does not have assigned accounts', function () {
    // Arrange
    $partner = Partner::factory()->for($this->company)->create([
        'receivable_account_id' => null,
        'payable_account_id' => null,
    ]);

    $startDate = Carbon::parse('2025-03-01');
    $endDate = Carbon::parse('2025-03-31');

    // Action & Assert
    $service = app(\Jmeryar\Accounting\Services\Reports\PartnerLedgerService::class);

    expect(fn () => $service->generate($this->company, $partner, $startDate, $endDate))
        ->toThrow(InvalidArgumentException::class, "Partner {$partner->name} does not have assigned receivable/payable accounts.");
});

test('it generates empty ledger for partner with no transactions', function () {
    // Arrange
    $currency = $this->company->currency->code;

    $receivableAccount = Account::factory()->for($this->company)->create(['type' => 'receivable']);
    $payableAccount = Account::factory()->for($this->company)->create(['type' => 'payable']);
    $partner = Partner::factory()->for($this->company)->create([
        'receivable_account_id' => $receivableAccount->id,
        'payable_account_id' => $payableAccount->id,
    ]);

    $startDate = Carbon::parse('2025-03-01');
    $endDate = Carbon::parse('2025-03-31');

    // Action
    $service = app(\Jmeryar\Accounting\Services\Reports\PartnerLedgerService::class);
    $report = $service->generate($this->company, $partner, $startDate, $endDate);

    // Assert
    expect($report->openingBalance)->toEqual(Money::of('0', $currency));
    expect($report->transactionLines)->toHaveCount(0);
    expect($report->closingBalance)->toEqual(Money::of('0', $currency));
});

test('it generates ledger for vendor with bills and payments', function () {
    // Arrange
    $currency = $this->company->currency->code;

    $receivableAccount = Account::factory()->for($this->company)->create(['type' => 'receivable']);
    $payableAccount = Account::factory()->for($this->company)->create(['type' => 'payable']);
    $partner = Partner::factory()->for($this->company)->create([
        'receivable_account_id' => $receivableAccount->id,
        'payable_account_id' => $payableAccount->id,
    ]);

    $purchaseJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Purchase]);
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $expenseAccount = Account::factory()->for($this->company)->create(['type' => 'expense']);

    $startDate = Carbon::parse('2025-03-01');
    $endDate = Carbon::parse('2025-03-31');

    // Transaction 1: Vendor bill within the period (300,000 IQD)
    $entry1 = JournalEntry::factory()->for($this->company)->for($purchaseJournal)
        ->create(['entry_date' => '2025-03-05', 'state' => 'posted', 'reference' => 'BILL/001', 'currency_id' => $this->company->currency_id]);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry1->id, 'account_id' => $expenseAccount->id, 'debit' => '300000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry1->id, 'account_id' => $payableAccount->id, 'debit' => '0', 'credit' => '300000']);

    // Transaction 2: Payment made within the period (150,000 IQD)
    $entry2 = JournalEntry::factory()->for($this->company)->for($bankJournal)
        ->create(['entry_date' => '2025-03-15', 'state' => 'posted', 'reference' => 'PAY/001', 'currency_id' => $this->company->currency_id]);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry2->id, 'account_id' => $payableAccount->id, 'debit' => '150000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry2->id, 'account_id' => $expenseAccount->id, 'debit' => '0', 'credit' => '150000']);

    // Action
    $service = app(\Jmeryar\Accounting\Services\Reports\PartnerLedgerService::class);
    $report = $service->generate($this->company, $partner, $startDate, $endDate);

    // Assert
    expect($report->openingBalance)->toEqual(Money::of('0', $currency));
    expect($report->transactionLines)->toHaveCount(2);

    // Line 1: Vendor Bill (credit increases what we owe)
    $line1 = $report->transactionLines[0];
    expect($line1->transactionType)->toBe('Vendor Bill');
    expect($line1->reference)->toBe('BILL/001');
    expect($line1->credit)->toEqual(Money::of('300000', $currency));
    expect($line1->balance)->toEqual(Money::of('300000', $currency)); // We owe 300k (CORRECTED: positive = we owe vendor)

    // Line 2: Payment (debit decreases what we owe)
    $line2 = $report->transactionLines[1];
    expect($line2->transactionType)->toBe('Payment');
    expect($line2->reference)->toBe('PAY/001');
    expect($line2->debit)->toEqual(Money::of('150000', $currency));
    expect($line2->balance)->toEqual(Money::of('150000', $currency)); // We still owe 150k (CORRECTED: positive = we owe vendor)

    expect($report->closingBalance)->toEqual(Money::of('150000', $currency)); // CORRECTED: positive = we owe vendor
});

test('it only includes posted transactions', function () {
    // Arrange
    $currency = $this->company->currency->code;

    $receivableAccount = Account::factory()->for($this->company)->create(['type' => 'receivable']);
    $payableAccount = Account::factory()->for($this->company)->create(['type' => 'payable']);
    $partner = Partner::factory()->for($this->company)->create([
        'receivable_account_id' => $receivableAccount->id,
        'payable_account_id' => $payableAccount->id,
    ]);

    $salesJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Sale]);
    $otherAccount = Account::factory()->for($this->company)->create(['type' => 'income']);

    $startDate = Carbon::parse('2025-03-01');
    $endDate = Carbon::parse('2025-03-31');

    // Posted transaction
    $entry1 = JournalEntry::factory()->for($this->company)->for($salesJournal)
        ->create(['entry_date' => '2025-03-10', 'state' => 'posted', 'reference' => 'INV/POSTED/001', 'currency_id' => $this->company->currency_id]);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry1->id, 'account_id' => $receivableAccount->id, 'debit' => '500000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry1->id, 'account_id' => $otherAccount->id, 'debit' => '0', 'credit' => '500000']);

    // Draft transaction (should be ignored)
    $entry2 = JournalEntry::factory()->for($this->company)->for($salesJournal)
        ->create(['entry_date' => '2025-03-15', 'state' => 'draft', 'reference' => 'INV/DRAFT/001', 'currency_id' => $this->company->currency_id]);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry2->id, 'account_id' => $receivableAccount->id, 'debit' => '300000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $entry2->id, 'account_id' => $otherAccount->id, 'debit' => '0', 'credit' => '300000']);

    // Action
    $service = app(\Jmeryar\Accounting\Services\Reports\PartnerLedgerService::class);
    $report = $service->generate($this->company, $partner, $startDate, $endDate);

    // Assert - Should only include the posted transaction
    expect($report->transactionLines)->toHaveCount(1);
    expect($report->transactionLines->first()->reference)->toBe('INV/POSTED/001');
    expect($report->closingBalance)->toEqual(Money::of('500000', $currency));
});

// 🚨 NEW FAILING TESTS - These describe the CORRECT behavior we want to implement

test('it correctly identifies vendor vs customer context and shows proper balance interpretation', function () {
    // This test will FAIL with current implementation but describes correct behavior

    // Arrange - Create a vendor partner
    $currency = $this->company->currency->code;
    $receivableAccount = Account::factory()->for($this->company)->create(['type' => 'receivable']);
    $payableAccount = Account::factory()->for($this->company)->create(['type' => 'payable']);
    $vendor = Partner::factory()->for($this->company)->create([
        'type' => 'vendor',
        'receivable_account_id' => $receivableAccount->id,
        'payable_account_id' => $payableAccount->id,
    ]);

    $purchaseJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Purchase]);
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $expenseAccount = Account::factory()->for($this->company)->create(['type' => 'expense']);
    $bankAccount = Account::factory()->for($this->company)->create(['type' => 'bank_and_cash']);

    $startDate = Carbon::parse('2025-03-01');
    $endDate = Carbon::parse('2025-03-31');

    // Vendor Bill: Dr. Expense 1,000,000 / Cr. Accounts Payable 1,000,000
    $billEntry = JournalEntry::factory()->for($this->company)->for($purchaseJournal)
        ->create(['entry_date' => '2025-03-05', 'state' => 'posted', 'reference' => 'BILL/001']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $billEntry->id, 'account_id' => $expenseAccount->id, 'debit' => '1000000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $billEntry->id, 'account_id' => $payableAccount->id, 'debit' => '0', 'credit' => '1000000']);

    // Payment: Dr. Accounts Payable 600,000 / Cr. Bank 600,000
    $paymentEntry = JournalEntry::factory()->for($this->company)->for($bankJournal)
        ->create(['entry_date' => '2025-03-15', 'state' => 'posted', 'reference' => 'PAY/001']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $paymentEntry->id, 'account_id' => $payableAccount->id, 'debit' => '600000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $paymentEntry->id, 'account_id' => $bankAccount->id, 'debit' => '0', 'credit' => '600000']);

    // Action
    $service = app(\Jmeryar\Accounting\Services\Reports\PartnerLedgerService::class);
    $report = $service->generate($this->company, $vendor, $startDate, $endDate);

    // Assert - CORRECT behavior for vendor ledger
    expect($report->transactionLines)->toHaveCount(2);

    // Bill line: Shows as credit (we owe them more)
    $billLine = $report->transactionLines[0];
    expect($billLine->transactionType)->toBe('Vendor Bill');
    expect($billLine->credit)->toEqual(Money::of('1000000', $currency));
    expect($billLine->debit)->toEqual(Money::of('0', $currency));

    // Payment line: Shows as debit (we owe them less)
    $paymentLine = $report->transactionLines[1];
    expect($paymentLine->transactionType)->toBe('Payment');
    expect($paymentLine->debit)->toEqual(Money::of('600000', $currency));
    expect($paymentLine->credit)->toEqual(Money::of('0', $currency));

    // Final balance: 400,000 IQD we still owe (positive balance for vendor = we owe them)
    expect($report->closingBalance)->toEqual(Money::of('400000', $currency));
});

test('it correctly handles customer ledger with invoices and payments', function () {
    // This test will FAIL with current implementation but describes correct behavior

    // Arrange - Create a customer partner
    $currency = $this->company->currency->code;
    $receivableAccount = Account::factory()->for($this->company)->create(['type' => 'receivable']);
    $payableAccount = Account::factory()->for($this->company)->create(['type' => 'payable']);
    $customer = Partner::factory()->for($this->company)->create([
        'type' => 'customer',
        'receivable_account_id' => $receivableAccount->id,
        'payable_account_id' => $payableAccount->id,
    ]);

    $salesJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Sale]);
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $revenueAccount = Account::factory()->for($this->company)->create(['type' => 'income']);
    $bankAccount = Account::factory()->for($this->company)->create(['type' => 'bank_and_cash']);

    $startDate = Carbon::parse('2025-03-01');
    $endDate = Carbon::parse('2025-03-31');

    // Invoice: Dr. Accounts Receivable 800,000 / Cr. Revenue 800,000
    $invoiceEntry = JournalEntry::factory()->for($this->company)->for($salesJournal)
        ->create(['entry_date' => '2025-03-05', 'state' => 'posted', 'reference' => 'INV/001']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $invoiceEntry->id, 'account_id' => $receivableAccount->id, 'debit' => '800000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $invoiceEntry->id, 'account_id' => $revenueAccount->id, 'debit' => '0', 'credit' => '800000']);

    // Payment: Dr. Bank 500,000 / Cr. Accounts Receivable 500,000
    $paymentEntry = JournalEntry::factory()->for($this->company)->for($bankJournal)
        ->create(['entry_date' => '2025-03-15', 'state' => 'posted', 'reference' => 'PMT/001']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $paymentEntry->id, 'account_id' => $bankAccount->id, 'debit' => '500000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $paymentEntry->id, 'account_id' => $receivableAccount->id, 'debit' => '0', 'credit' => '500000']);

    // Action
    $service = app(\Jmeryar\Accounting\Services\Reports\PartnerLedgerService::class);
    $report = $service->generate($this->company, $customer, $startDate, $endDate);

    // Assert - CORRECT behavior for customer ledger
    expect($report->transactionLines)->toHaveCount(2);

    // Invoice line: Shows as debit (they owe us more)
    $invoiceLine = $report->transactionLines[0];
    expect($invoiceLine->transactionType)->toBe('Invoice');
    expect($invoiceLine->debit)->toEqual(Money::of('800000', $currency));
    expect($invoiceLine->credit)->toEqual(Money::of('0', $currency));

    // Payment line: Shows as credit (they owe us less)
    $paymentLine = $report->transactionLines[1];
    expect($paymentLine->transactionType)->toBe('Payment');
    expect($paymentLine->credit)->toEqual(Money::of('500000', $currency));
    expect($paymentLine->debit)->toEqual(Money::of('0', $currency));

    // Final balance: 300,000 IQD they still owe us (positive balance for customer = they owe us)
    expect($report->closingBalance)->toEqual(Money::of('300000', $currency));
});

test('it handles overpayment scenarios correctly for vendors', function () {
    // This test describes correct behavior for overpayment scenarios

    // Arrange - Vendor with overpayment
    $currency = $this->company->currency->code;
    $receivableAccount = Account::factory()->for($this->company)->create(['type' => 'receivable']);
    $payableAccount = Account::factory()->for($this->company)->create(['type' => 'payable']);
    $vendor = Partner::factory()->for($this->company)->create([
        'type' => 'vendor',
        'receivable_account_id' => $receivableAccount->id,
        'payable_account_id' => $payableAccount->id,
    ]);

    $purchaseJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Purchase]);
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $expenseAccount = Account::factory()->for($this->company)->create(['type' => 'expense']);
    $bankAccount = Account::factory()->for($this->company)->create(['type' => 'bank_and_cash']);

    $startDate = Carbon::parse('2025-03-01');
    $endDate = Carbon::parse('2025-03-31');

    // Vendor Bill: 500,000 IQD
    $billEntry = JournalEntry::factory()->for($this->company)->for($purchaseJournal)
        ->create(['entry_date' => '2025-03-05', 'state' => 'posted', 'reference' => 'BILL/001']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $billEntry->id, 'account_id' => $expenseAccount->id, 'debit' => '500000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $billEntry->id, 'account_id' => $payableAccount->id, 'debit' => '0', 'credit' => '500000']);

    // Overpayment: 800,000 IQD (300,000 more than bill)
    $paymentEntry = JournalEntry::factory()->for($this->company)->for($bankJournal)
        ->create(['entry_date' => '2025-03-15', 'state' => 'posted', 'reference' => 'PAY/001']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $paymentEntry->id, 'account_id' => $payableAccount->id, 'debit' => '800000', 'credit' => '0']);
    JournalEntryLine::create(['company_id' => $this->company->id, 'journal_entry_id' => $paymentEntry->id, 'account_id' => $bankAccount->id, 'debit' => '0', 'credit' => '800000']);

    // Action
    $service = app(\Jmeryar\Accounting\Services\Reports\PartnerLedgerService::class);
    $report = $service->generate($this->company, $vendor, $startDate, $endDate);

    // Assert - Should show negative balance (they owe us money back)
    expect($report->closingBalance)->toEqual(Money::of('-300000', $currency));
    expect($report->transactionLines)->toHaveCount(2);
});
