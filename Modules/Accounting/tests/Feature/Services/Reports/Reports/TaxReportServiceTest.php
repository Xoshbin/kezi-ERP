<?php

namespace Modules\Accounting\Tests\Feature\Services\Reports;

use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalType;
use App\Enums\Accounting\TaxType;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Tax;
use App\Services\Reports\TaxReportService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('it generates a tax report with sales tax only', function () {
    // Arrange
    $company = $this->company;
    $currency = $company->currency->code;
    $startDate = Carbon::parse('2025-04-01');
    $endDate = Carbon::parse('2025-04-30');

    // Create sales tax and account
    $salesTaxAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentLiabilities]);
    $salesTax = Tax::factory()->for($company)->create([
        'name' => 'VAT 15% (Sales)',
        'rate' => 15.0,
        'type' => TaxType::Sales,
        'tax_account_id' => $salesTaxAccount->id,
        'is_active' => true,
    ]);

    // Create sales journal
    $salesJournal = Journal::factory()->for($company)->create(['type' => JournalType::Sale]);

    // Create journal entry with tax
    $journalEntry = JournalEntry::factory()->for($company)->create([
        'journal_id' => $salesJournal->id,
        'entry_date' => '2025-04-15',
        'is_posted' => true,
    ]);

    // Create journal entry lines: AR debit, Income credit, Tax credit
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Receivable])->id,
        'debit' => Money::of(1150, $currency), // 1000 + 150 tax
        'credit' => Money::zero($currency),
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income])->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(1000, $currency),
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $salesTaxAccount->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(150, $currency), // Tax amount
    ]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\TaxReportService::class);
    $report = $service->generate($company, $startDate, $endDate);

    // Assert
    expect($report->outputTaxLines)->toHaveCount(1);
    expect($report->inputTaxLines)->toHaveCount(0);

    $outputLine = $report->outputTaxLines->first();
    expect($outputLine->taxId)->toBe($salesTax->id);
    expect($outputLine->taxName)->toBe('VAT 15% (Sales)');
    expect($outputLine->taxRate)->toBe(15.0);
    expect($outputLine->taxAmount)->toEqual(Money::of(150, $currency));
    expect($outputLine->netAmount)->toEqual(Money::of(1000, $currency));

    expect($report->totalOutputTax)->toEqual(Money::of(150, $currency));
    expect($report->totalInputTax)->toEqual(Money::zero($currency));
    expect($report->netTaxPayable)->toEqual(Money::of(150, $currency));
});

test('it generates a tax report with purchase tax only', function () {
    // Arrange
    $company = $this->company;
    $currency = $company->currency->code;
    $startDate = Carbon::parse('2025-04-01');
    $endDate = Carbon::parse('2025-04-30');

    // Create purchase tax and account
    $purchaseTaxAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentAssets]);
    $purchaseTax = Tax::factory()->for($company)->create([
        'name' => 'VAT 15% (Purchase)',
        'rate' => 15.0,
        'type' => TaxType::Purchase,
        'tax_account_id' => $purchaseTaxAccount->id,
        'is_active' => true,
    ]);

    // Create purchase journal
    $purchaseJournal = Journal::factory()->for($company)->create(['type' => JournalType::Purchase]);

    // Create journal entry with tax
    $journalEntry = JournalEntry::factory()->for($company)->create([
        'journal_id' => $purchaseJournal->id,
        'entry_date' => '2025-04-20',
        'is_posted' => true,
    ]);

    // Create journal entry lines: Expense debit, Tax debit, AP credit
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense])->id,
        'debit' => Money::of(600, $currency),
        'credit' => Money::zero($currency),
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $purchaseTaxAccount->id,
        'debit' => Money::of(90, $currency), // Tax amount
        'credit' => Money::zero($currency),
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Payable])->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(690, $currency), // 600 + 90 tax
    ]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\TaxReportService::class);
    $report = $service->generate($company, $startDate, $endDate);

    // Assert
    expect($report->outputTaxLines)->toHaveCount(0);
    expect($report->inputTaxLines)->toHaveCount(1);

    $inputLine = $report->inputTaxLines->first();
    expect($inputLine->taxId)->toBe($purchaseTax->id);
    expect($inputLine->taxName)->toBe('VAT 15% (Purchase)');
    expect($inputLine->taxRate)->toBe(15.0);
    expect($inputLine->taxAmount)->toEqual(Money::of(90, $currency));
    expect($inputLine->netAmount)->toEqual(Money::of(600, $currency));

    expect($report->totalOutputTax)->toEqual(Money::zero($currency));
    expect($report->totalInputTax)->toEqual(Money::of(90, $currency));
    expect($report->netTaxPayable)->toEqual(Money::of(-90, $currency)); // Refundable
});

test('it generates a tax report with mixed sales and purchase taxes', function () {
    // Arrange
    $company = $this->company;
    $currency = $company->currency->code;
    $startDate = Carbon::parse('2025-04-01');
    $endDate = Carbon::parse('2025-04-30');

    // Create sales tax
    $salesTaxAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentLiabilities]);
    $salesTax = Tax::factory()->for($company)->create([
        'name' => 'VAT 15% (Sales)',
        'rate' => 15.0,
        'type' => TaxType::Sales,
        'tax_account_id' => $salesTaxAccount->id,
        'is_active' => true,
    ]);

    // Create purchase tax
    $purchaseTaxAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentAssets]);
    $purchaseTax = Tax::factory()->for($company)->create([
        'name' => 'VAT 15% (Purchase)',
        'rate' => 15.0,
        'type' => TaxType::Purchase,
        'tax_account_id' => $purchaseTaxAccount->id,
        'is_active' => true,
    ]);

    // Create journals
    $salesJournal = Journal::factory()->for($company)->create(['type' => JournalType::Sale]);
    $purchaseJournal = Journal::factory()->for($company)->create(['type' => JournalType::Purchase]);

    // Create sales journal entry
    $salesEntry = JournalEntry::factory()->for($company)->create([
        'journal_id' => $salesJournal->id,
        'entry_date' => '2025-04-15',
        'is_posted' => true,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $salesEntry->id,
        'account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Receivable])->id,
        'debit' => Money::of(1150, $currency),
        'credit' => Money::zero($currency),
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $salesEntry->id,
        'account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income])->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(1000, $currency),
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $salesEntry->id,
        'account_id' => $salesTaxAccount->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(150, $currency),
    ]);

    // Create purchase journal entry
    $purchaseEntry = JournalEntry::factory()->for($company)->create([
        'journal_id' => $purchaseJournal->id,
        'entry_date' => '2025-04-20',
        'is_posted' => true,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $purchaseEntry->id,
        'account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense])->id,
        'debit' => Money::of(600, $currency),
        'credit' => Money::zero($currency),
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $purchaseEntry->id,
        'account_id' => $purchaseTaxAccount->id,
        'debit' => Money::of(90, $currency),
        'credit' => Money::zero($currency),
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $purchaseEntry->id,
        'account_id' => \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Payable])->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(690, $currency),
    ]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\TaxReportService::class);
    $report = $service->generate($company, $startDate, $endDate);

    // Assert
    expect($report->outputTaxLines)->toHaveCount(1);
    expect($report->inputTaxLines)->toHaveCount(1);

    expect($report->totalOutputTax)->toEqual(Money::of(150, $currency));
    expect($report->totalInputTax)->toEqual(Money::of(90, $currency));
    expect($report->netTaxPayable)->toEqual(Money::of(60, $currency)); // 150 - 90
});

test('it excludes transactions outside the date range', function () {
    // Arrange
    $company = $this->company;
    $currency = $company->currency->code;
    $startDate = Carbon::parse('2025-04-01');
    $endDate = Carbon::parse('2025-04-30');

    // Create sales tax
    $salesTaxAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentLiabilities]);
    $salesTax = Tax::factory()->for($company)->create([
        'name' => 'VAT 15% (Sales)',
        'rate' => 15.0,
        'type' => TaxType::Sales,
        'tax_account_id' => $salesTaxAccount->id,
        'is_active' => true,
    ]);

    $salesJournal = Journal::factory()->for($company)->create(['type' => JournalType::Sale]);

    // Create journal entry BEFORE the date range
    $entryBefore = JournalEntry::factory()->for($company)->create([
        'journal_id' => $salesJournal->id,
        'entry_date' => '2025-03-15', // Before start date
        'is_posted' => true,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entryBefore->id,
        'account_id' => $salesTaxAccount->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(100, $currency),
    ]);

    // Create journal entry AFTER the date range
    $entryAfter = JournalEntry::factory()->for($company)->create([
        'journal_id' => $salesJournal->id,
        'entry_date' => '2025-05-15', // After end date
        'is_posted' => true,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entryAfter->id,
        'account_id' => $salesTaxAccount->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(200, $currency),
    ]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\TaxReportService::class);
    $report = $service->generate($company, $startDate, $endDate);

    // Assert - should be empty as no transactions in date range
    expect($report->outputTaxLines)->toHaveCount(0);
    expect($report->inputTaxLines)->toHaveCount(0);
    expect($report->totalOutputTax)->toEqual(Money::zero($currency));
    expect($report->totalInputTax)->toEqual(Money::zero($currency));
    expect($report->netTaxPayable)->toEqual(Money::zero($currency));
});

test('it excludes draft journal entries', function () {
    // Arrange
    $company = $this->company;
    $currency = $company->currency->code;
    $startDate = Carbon::parse('2025-04-01');
    $endDate = Carbon::parse('2025-04-30');

    // Create sales tax
    $salesTaxAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentLiabilities]);
    $salesTax = Tax::factory()->for($company)->create([
        'name' => 'VAT 15% (Sales)',
        'rate' => 15.0,
        'type' => TaxType::Sales,
        'tax_account_id' => $salesTaxAccount->id,
        'is_active' => true,
    ]);

    $salesJournal = Journal::factory()->for($company)->create(['type' => JournalType::Sale]);

    // Create DRAFT journal entry (should be excluded)
    $draftEntry = JournalEntry::factory()->for($company)->create([
        'journal_id' => $salesJournal->id,
        'entry_date' => '2025-04-15',
        'is_posted' => false, // Draft
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $draftEntry->id,
        'account_id' => $salesTaxAccount->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(150, $currency),
    ]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\TaxReportService::class);
    $report = $service->generate($company, $startDate, $endDate);

    // Assert - should be empty as draft entries are excluded
    expect($report->outputTaxLines)->toHaveCount(0);
    expect($report->inputTaxLines)->toHaveCount(0);
    expect($report->totalOutputTax)->toEqual(Money::zero($currency));
    expect($report->totalInputTax)->toEqual(Money::zero($currency));
    expect($report->netTaxPayable)->toEqual(Money::zero($currency));
});

test('it excludes inactive taxes', function () {
    // Arrange
    $company = $this->company;
    $currency = $company->currency->code;
    $startDate = Carbon::parse('2025-04-01');
    $endDate = Carbon::parse('2025-04-30');

    // Create INACTIVE sales tax
    $salesTaxAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentLiabilities]);
    $inactiveTax = Tax::factory()->for($company)->create([
        'name' => 'VAT 15% (Inactive)',
        'rate' => 15.0,
        'type' => TaxType::Sales,
        'tax_account_id' => $salesTaxAccount->id,
        'is_active' => false, // Inactive
    ]);

    $salesJournal = Journal::factory()->for($company)->create(['type' => JournalType::Sale]);

    // Create journal entry with inactive tax
    $journalEntry = JournalEntry::factory()->for($company)->create([
        'journal_id' => $salesJournal->id,
        'entry_date' => '2025-04-15',
        'is_posted' => true,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $salesTaxAccount->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(150, $currency),
    ]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\TaxReportService::class);
    $report = $service->generate($company, $startDate, $endDate);

    // Assert - should be empty as inactive taxes are excluded
    expect($report->outputTaxLines)->toHaveCount(0);
    expect($report->inputTaxLines)->toHaveCount(0);
    expect($report->totalOutputTax)->toEqual(Money::zero($currency));
    expect($report->totalInputTax)->toEqual(Money::zero($currency));
    expect($report->netTaxPayable)->toEqual(Money::zero($currency));
});

test('it handles multiple tax rates correctly', function () {
    // Arrange
    $company = $this->company;
    $currency = $company->currency->code;
    $startDate = Carbon::parse('2025-04-01');
    $endDate = Carbon::parse('2025-04-30');

    // Create 15% sales tax
    $salesTax15Account = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentLiabilities]);
    $salesTax15 = Tax::factory()->for($company)->create([
        'name' => 'VAT 15% (Sales)',
        'rate' => 15.0,
        'type' => TaxType::Sales,
        'tax_account_id' => $salesTax15Account->id,
        'is_active' => true,
    ]);

    // Create 10% sales tax
    $salesTax10Account = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentLiabilities]);
    $salesTax10 = Tax::factory()->for($company)->create([
        'name' => 'VAT 10% (Sales)',
        'rate' => 10.0,
        'type' => TaxType::Sales,
        'tax_account_id' => $salesTax10Account->id,
        'is_active' => true,
    ]);

    $salesJournal = Journal::factory()->for($company)->create(['type' => JournalType::Sale]);

    // Create journal entry with 15% tax
    $entry15 = JournalEntry::factory()->for($company)->create([
        'journal_id' => $salesJournal->id,
        'entry_date' => '2025-04-15',
        'is_posted' => true,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry15->id,
        'account_id' => $salesTax15Account->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(150, $currency), // 15% of 1000
    ]);

    // Create journal entry with 10% tax
    $entry10 = JournalEntry::factory()->for($company)->create([
        'journal_id' => $salesJournal->id,
        'entry_date' => '2025-04-20',
        'is_posted' => true,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry10->id,
        'account_id' => $salesTax10Account->id,
        'debit' => Money::zero($currency),
        'credit' => Money::of(50, $currency), // 10% of 500
    ]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\TaxReportService::class);
    $report = $service->generate($company, $startDate, $endDate);

    // Assert
    expect($report->outputTaxLines)->toHaveCount(2);
    expect($report->inputTaxLines)->toHaveCount(0);

    // Check individual tax lines
    $tax15Line = $report->outputTaxLines->firstWhere('taxId', $salesTax15->id);
    $tax10Line = $report->outputTaxLines->firstWhere('taxId', $salesTax10->id);

    expect($tax15Line->taxAmount)->toEqual(Money::of(150, $currency));
    expect($tax15Line->taxRate)->toBe(15.0);
    expect($tax10Line->taxAmount)->toEqual(Money::of(50, $currency));
    expect($tax10Line->taxRate)->toBe(10.0);

    expect($report->totalOutputTax)->toEqual(Money::of(200, $currency)); // 150 + 50
    expect($report->totalInputTax)->toEqual(Money::zero($currency));
    expect($report->netTaxPayable)->toEqual(Money::of(200, $currency));
});
