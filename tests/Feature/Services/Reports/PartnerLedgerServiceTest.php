<?php

namespace Tests\Feature\Services\Reports;

use App\Enums\Accounting\JournalType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Partner;
use App\Services\Reports\PartnerLedgerService;
use Brick\Money\Money;
use Carbon\Carbon;
use InvalidArgumentException;

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
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry1->id, 'account_id' => $receivableAccount->id, 'debit' => '1000000', 'credit' => '0']);
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry1->id, 'account_id' => $otherAccount->id, 'debit' => '0', 'credit' => '1000000']);

    // Transaction 2: New invoice within the period (500,000 IQD)
    $entry2 = JournalEntry::factory()->for($this->company)->for($salesJournal)
        ->create(['entry_date' => '2025-03-10', 'state' => 'posted', 'reference' => 'INV/NEW/001', 'currency_id' => $this->company->currency_id]);
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry2->id, 'account_id' => $receivableAccount->id, 'debit' => '500000', 'credit' => '0']);
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry2->id, 'account_id' => $otherAccount->id, 'debit' => '0', 'credit' => '500000']);

    // Transaction 3: Payment received within the period (700,000 IQD)
    $entry3 = JournalEntry::factory()->for($this->company)->for($bankJournal)
        ->create(['entry_date' => '2025-03-20', 'state' => 'posted', 'reference' => 'PMT/001', 'currency_id' => $this->company->currency_id]);
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry3->id, 'account_id' => $receivableAccount->id, 'debit' => '0', 'credit' => '700000']);
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry3->id, 'account_id' => $otherAccount->id, 'debit' => '700000', 'credit' => '0']);

    // Action
    $service = app(PartnerLedgerService::class);
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
    $service = app(PartnerLedgerService::class);

    expect(fn() => $service->generate($this->company, $partner, $startDate, $endDate))
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
    $service = app(PartnerLedgerService::class);
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
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry1->id, 'account_id' => $expenseAccount->id, 'debit' => '300000', 'credit' => '0']);
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry1->id, 'account_id' => $payableAccount->id, 'debit' => '0', 'credit' => '300000']);

    // Transaction 2: Payment made within the period (150,000 IQD)
    $entry2 = JournalEntry::factory()->for($this->company)->for($bankJournal)
        ->create(['entry_date' => '2025-03-15', 'state' => 'posted', 'reference' => 'PAY/001', 'currency_id' => $this->company->currency_id]);
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry2->id, 'account_id' => $payableAccount->id, 'debit' => '150000', 'credit' => '0']);
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry2->id, 'account_id' => $expenseAccount->id, 'debit' => '0', 'credit' => '150000']);

    // Action
    $service = app(PartnerLedgerService::class);
    $report = $service->generate($this->company, $partner, $startDate, $endDate);

    // Assert
    expect($report->openingBalance)->toEqual(Money::of('0', $currency));
    expect($report->transactionLines)->toHaveCount(2);

    // Line 1: Vendor Bill (credit increases what we owe)
    $line1 = $report->transactionLines[0];
    expect($line1->transactionType)->toBe('Vendor Bill');
    expect($line1->reference)->toBe('BILL/001');
    expect($line1->credit)->toEqual(Money::of('300000', $currency));
    expect($line1->balance)->toEqual(Money::of('-300000', $currency)); // We owe 300k

    // Line 2: Payment (debit decreases what we owe)
    $line2 = $report->transactionLines[1];
    expect($line2->transactionType)->toBe('Payment');
    expect($line2->reference)->toBe('PAY/001');
    expect($line2->debit)->toEqual(Money::of('150000', $currency));
    expect($line2->balance)->toEqual(Money::of('-150000', $currency)); // We still owe 150k

    expect($report->closingBalance)->toEqual(Money::of('-150000', $currency));
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
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry1->id, 'account_id' => $receivableAccount->id, 'debit' => '500000', 'credit' => '0']);
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry1->id, 'account_id' => $otherAccount->id, 'debit' => '0', 'credit' => '500000']);

    // Draft transaction (should be ignored)
    $entry2 = JournalEntry::factory()->for($this->company)->for($salesJournal)
        ->create(['entry_date' => '2025-03-15', 'state' => 'draft', 'reference' => 'INV/DRAFT/001', 'currency_id' => $this->company->currency_id]);
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry2->id, 'account_id' => $receivableAccount->id, 'debit' => '300000', 'credit' => '0']);
    \App\Models\JournalEntryLine::create(['journal_entry_id' => $entry2->id, 'account_id' => $otherAccount->id, 'debit' => '0', 'credit' => '300000']);

    // Action
    $service = app(PartnerLedgerService::class);
    $report = $service->generate($this->company, $partner, $startDate, $endDate);

    // Assert - Should only include the posted transaction
    expect($report->transactionLines)->toHaveCount(1);
    expect($report->transactionLines->first()->reference)->toBe('INV/POSTED/001');
    expect($report->closingBalance)->toEqual(Money::of('500000', $currency));
});
