<?php

namespace Modules\Accounting\Tests\Feature\Filament\Reports;

use Carbon\Carbon;
use function Pest\Livewire\livewire;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;

use Tests\Traits\WithConfiguredCompany;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Filament\Clusters\Accounting\Pages\Reports\ViewBalanceSheet;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('ViewBalanceSheet Filament Page', function () {
    test('it can render the page', function () {
        livewire(ViewBalanceSheet::class)
            ->assertSuccessful();
    });

    test('it can generate a balance sheet report', function () {
        // Arrange - Create test data
        $currency = $this->company->currency->code;
        $journal = Journal::factory()->for($this->company)->create();

        // Create accounts
        $bankAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
        $arAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Receivable]);
        $apAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Payable]);
        $equityAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Equity]);
        $salesAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income]);
        $expenseAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense]);

        // Create transactions
        // 1. Initial capital investment
        $entry1 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-05', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 1000000]);

        // 2. Sale on account
        $entry2 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry2)->create(['account_id' => $arAccount->id, 'debit' => 500000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry2)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 500000]);

        // 3. Purchase on account
        $entry3 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-02-15', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry3)->create(['account_id' => $bankAccount->id, 'debit' => 200000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry3)->create(['account_id' => $apAccount->id, 'debit' => 0, 'credit' => 200000]);

        // 4. Pay expense
        $entry4 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-03-01', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry4)->create(['account_id' => $expenseAccount->id, 'debit' => 100000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry4)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 100000]);

        // Act & Assert
        livewire(ViewBalanceSheet::class)
            ->fillForm([
                'asOfDate' => '2025-03-31',
            ])
            ->call('generateReport')
            ->assertHasNoFormErrors()
            ->assertCount('reportData.assetLines', 2) // Bank and AR
            ->assertCount('reportData.liabilityLines', 1) // AP
            ->assertCount('reportData.equityLines', 1) // Equity
            ->assertSet('reportData.currentYearEarningsAmount', 400000.0) // 500k Revenue - 100k Expense
            ->assertSet('reportData.isCurrentYearLoss', false);
    });

    test('it validates as-of date input', function () {
        livewire(ViewBalanceSheet::class)
            ->fillForm([
                'asOfDate' => null,
            ])
            ->call('generateReport')
            ->assertHasFormErrors(['asOfDate']);
    });

    test('it sets default as-of date to end of current month', function () {
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

        livewire(ViewBalanceSheet::class)
            ->assertSet('asOfDate', $endOfMonth);
    });

    test('it handles negative current year earnings correctly', function () {
        // Arrange - Create test data with net loss
        $currency = $this->company->currency->code;
        $journal = Journal::factory()->for($this->company)->create();

        $bankAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
        $equityAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Equity]);
        $salesAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income]);
        $expenseAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense]);

        // Initial capital
        $entry1 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-05', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 1000000]);

        // Small revenue
        $entry2 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 100000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry2)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 100000]);

        // Large expense (creating net loss)
        $entry3 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-03-01', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry3)->create(['account_id' => $expenseAccount->id, 'debit' => 500000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry3)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 500000]);

        // Act & Assert
        livewire(ViewBalanceSheet::class)
            ->fillForm([
                'asOfDate' => '2025-03-31',
            ])
            ->call('generateReport')
            ->assertHasNoFormErrors()
            ->assertSet('reportData.currentYearEarningsAmount', -400000.0) // 100k Revenue - 500k Expense = -400k Net Loss
            ->assertSet('reportData.isCurrentYearLoss', true);
    });

    test('it excludes draft transactions from the report', function () {
        // Arrange
        $currency = $this->company->currency->code;
        $journal = Journal::factory()->for($this->company)->create();

        $bankAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
        $equityAccount = Account::factory()->for($this->company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Equity]);

        // Posted transaction
        $entry1 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-05', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 1000000]);

        // Draft transaction (should be ignored)
        $entry2 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Draft]);
        JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 500000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry2)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 500000]);

        // Act & Assert - Only the posted transaction should be included
        livewire(ViewBalanceSheet::class)
            ->fillForm([
                'asOfDate' => '2025-03-31',
            ])
            ->call('generateReport')
            ->assertHasNoFormErrors()
            ->assertCount('reportData.assetLines', 1) // Only bank account from posted transaction
            ->assertCount('reportData.equityLines', 1); // Only equity from posted transaction
    });
});
