<?php

namespace Jmeryar\Accounting\Tests\Feature\Services\Reports;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\DataTransferObjects\Reports\ProfitAndLossStatementDTO;
use Jmeryar\Accounting\Enums\Accounting\JournalEntryState;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Accounting\Models\JournalEntryLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('ProfitAndLossStatementService', function () {
    test('it calculates the profit and loss statement correctly for a given period', function () {
        // Arrange
        $currency = $this->company->currency->code;
        $journal = Journal::factory()->for($this->company)->create();

        $salesAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Income]);
        $otherIncomeAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::OtherIncome]);
        $cogsAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::CostOfRevenue]);
        $rentAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Expense]);
        $depreciationAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Depreciation]);
        $receivableAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Receivable]);
        $bankAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::BankAndCash]);
        $inventoryAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::CurrentAssets]);

        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-01-31');

        // -- Transactions WITHIN the date range --
        // Note: Use major units - MoneyCast will convert to minor units automatically

        // 1. Sale of 1,000,000 IQD on Jan 15
        $salesEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-15', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($salesEntry)->create(['account_id' => $receivableAccount->id, 'debit' => 1000000, 'credit' => 0]);
        JournalEntryLine::factory()->for($salesEntry)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 1000000]);

        // 2. Other income of 200,000 IQD on Jan 10
        $otherIncomeEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-10', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($otherIncomeEntry)->create(['account_id' => $bankAccount->id, 'debit' => 200000, 'credit' => 0]);
        JournalEntryLine::factory()->for($otherIncomeEntry)->create(['account_id' => $otherIncomeAccount->id, 'debit' => 0, 'credit' => 200000]);

        // 3. Cost of Goods Sold of 400,000 IQD on Jan 15
        $cogsEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-15', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($cogsEntry)->create(['account_id' => $cogsAccount->id, 'debit' => 400000, 'credit' => 0]);
        JournalEntryLine::factory()->for($cogsEntry)->create(['account_id' => $inventoryAccount->id, 'debit' => 0, 'credit' => 400000]);

        // 4. Rent expense of 150,000 IQD on Jan 20
        $rentEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-20', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($rentEntry)->create(['account_id' => $rentAccount->id, 'debit' => 150000, 'credit' => 0]);
        JournalEntryLine::factory()->for($rentEntry)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 150000]);

        // 5. Depreciation expense of 50,000 IQD on Jan 25
        $depreciationEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-25', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($depreciationEntry)->create(['account_id' => $depreciationAccount->id, 'debit' => 50000, 'credit' => 0]);
        JournalEntryLine::factory()->for($depreciationEntry)->create(['account_id' => $inventoryAccount->id, 'debit' => 0, 'credit' => 50000]);

        // -- Transactions OUTSIDE the date range (should be ignored) --
        $ignoredEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-02-05', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($ignoredEntry)->create(['account_id' => $receivableAccount->id, 'debit' => 100000, 'credit' => 0]);
        JournalEntryLine::factory()->for($ignoredEntry)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 100000]);

        // -- DRAFT Transaction (should be ignored) --
        $draftEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-18', 'state' => JournalEntryState::Draft]);
        JournalEntryLine::factory()->for($draftEntry)->create(['account_id' => $rentAccount->id, 'debit' => 25000, 'credit' => 0]);
        JournalEntryLine::factory()->for($draftEntry)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 25000]);

        // Action
        $service = app(\Jmeryar\Accounting\Services\Reports\ProfitAndLossStatementService::class);
        $report = $service->generate($this->company, $startDate, $endDate);

        // Assert
        expect($report)->toBeInstanceOf(ProfitAndLossStatementDTO::class);

        // Check Total Revenues (Sales + Other Income)
        expect($report->totalRevenue)->toEqual(Money::of('1200000', $currency)); // 1M + 200k
        expect($report->revenueLines)->toHaveCount(2);

        // Check individual revenue lines
        $salesLine = $report->revenueLines->firstWhere('accountId', $salesAccount->id);
        expect($salesLine)->not->toBeNull();
        expect($salesLine->accountName)->toBe($salesAccount->name);
        expect($salesLine->balance)->toEqual(Money::of('1000000', $currency));

        $otherIncomeLine = $report->revenueLines->firstWhere('accountId', $otherIncomeAccount->id);
        expect($otherIncomeLine)->not->toBeNull();
        expect($otherIncomeLine->balance)->toEqual(Money::of('200000', $currency));

        // Check Total Expenses (COGS + Rent + Depreciation)
        expect($report->totalExpenses)->toEqual(Money::of('600000', $currency)); // 400k + 150k + 50k
        expect($report->expenseLines)->toHaveCount(3);

        // Check individual expense lines
        $cogsLine = $report->expenseLines->firstWhere('accountId', $cogsAccount->id);
        expect($cogsLine)->not->toBeNull();
        expect($cogsLine->balance)->toEqual(Money::of('400000', $currency));

        $rentLine = $report->expenseLines->firstWhere('accountId', $rentAccount->id);
        expect($rentLine)->not->toBeNull();
        expect($rentLine->balance)->toEqual(Money::of('150000', $currency));

        $depreciationLine = $report->expenseLines->firstWhere('accountId', $depreciationAccount->id);
        expect($depreciationLine)->not->toBeNull();
        expect($depreciationLine->balance)->toEqual(Money::of('50000', $currency));

        // Check Net Income
        expect($report->netIncome)->toEqual(Money::of('600000', $currency)); // 1.2M - 600k
    });

    test('it returns empty report when no transactions exist in the period', function () {
        // Arrange
        $currency = $this->company->currency->code;
        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-01-31');

        // Action
        $service = app(\Jmeryar\Accounting\Services\Reports\ProfitAndLossStatementService::class);
        $report = $service->generate($this->company, $startDate, $endDate);

        // Assert
        expect($report)->toBeInstanceOf(ProfitAndLossStatementDTO::class);
        expect($report->revenueLines)->toHaveCount(0);
        expect($report->expenseLines)->toHaveCount(0);
        expect($report->totalRevenue)->toEqual(Money::zero($currency));
        expect($report->totalExpenses)->toEqual(Money::zero($currency));
        expect($report->netIncome)->toEqual(Money::zero($currency));
    });

    test('it handles negative net income correctly', function () {
        // Arrange
        $currency = $this->company->currency->code;
        $journal = Journal::factory()->for($this->company)->create();

        $salesAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Income]);
        $expenseAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Expense]);
        $receivableAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Receivable]);
        $bankAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::BankAndCash]);

        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-01-31');

        // Small revenue
        $salesEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-15', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($salesEntry)->create(['account_id' => $receivableAccount->id, 'debit' => 100000, 'credit' => 0]);
        JournalEntryLine::factory()->for($salesEntry)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 100000]);

        // Large expense
        $expenseEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-20', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($expenseEntry)->create(['account_id' => $expenseAccount->id, 'debit' => 500000, 'credit' => 0]);
        JournalEntryLine::factory()->for($expenseEntry)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 500000]);

        // Action
        $service = app(\Jmeryar\Accounting\Services\Reports\ProfitAndLossStatementService::class);
        $report = $service->generate($this->company, $startDate, $endDate);

        // Assert
        expect($report->totalRevenue)->toEqual(Money::of('100000', $currency));
        expect($report->totalExpenses)->toEqual(Money::of('500000', $currency));
        expect($report->netIncome)->toEqual(Money::of('-400000', $currency)); // Net loss
    });

    test('it excludes balance sheet accounts from the report', function () {
        // Arrange
        $currency = $this->company->currency->code;
        $journal = Journal::factory()->for($this->company)->create();

        $assetAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::CurrentAssets]);
        $liabilityAccount = Account::factory()->for($this->company)->create(['type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::CurrentLiabilities]);

        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-01-31');

        // Create transactions with balance sheet accounts only
        $balanceSheetEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-15', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($balanceSheetEntry)->create(['account_id' => $assetAccount->id, 'debit' => 1000000, 'credit' => 0]);
        JournalEntryLine::factory()->for($balanceSheetEntry)->create(['account_id' => $liabilityAccount->id, 'debit' => 0, 'credit' => 1000000]);

        // Action
        $service = app(\Jmeryar\Accounting\Services\Reports\ProfitAndLossStatementService::class);
        $report = $service->generate($this->company, $startDate, $endDate);

        // Assert - Should be empty since no P&L accounts were used
        expect($report->revenueLines)->toHaveCount(0);
        expect($report->expenseLines)->toHaveCount(0);
        expect($report->totalRevenue)->toEqual(Money::zero($currency));
        expect($report->totalExpenses)->toEqual(Money::zero($currency));
        expect($report->netIncome)->toEqual(Money::zero($currency));
    });
});
