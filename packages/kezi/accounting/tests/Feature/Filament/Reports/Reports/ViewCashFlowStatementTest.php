<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Reports;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\JournalEntryState;
use Kezi\Accounting\Filament\Clusters\Accounting\Pages\Reports\ViewCashFlowStatement;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('ViewCashFlowStatement Filament Page', function () {
    test('it can render the page', function () {
        livewire(ViewCashFlowStatement::class)
            ->assertSuccessful();
    });

    test('it can generate a cash flow statement report', function () {
        // Arrange - Create test data
        $journal = Journal::factory()->for($this->company)->create();

        // Create accounts
        $bankAccount = Account::factory()->for($this->company)->create(['type' => \Kezi\Accounting\Enums\Accounting\AccountType::BankAndCash]);
        $equityAccount = Account::factory()->for($this->company)->create(['type' => \Kezi\Accounting\Enums\Accounting\AccountType::Equity]);
        $salesAccount = Account::factory()->for($this->company)->create(['type' => \Kezi\Accounting\Enums\Accounting\AccountType::Income]);
        $fixedAssetAccount = Account::factory()->for($this->company)->create(['type' => \Kezi\Accounting\Enums\Accounting\AccountType::FixedAssets]);

        // 1. Capital investment (Financing)
        $entry1 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-05', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry1)->create(['account_id' => $bankAccount->id, 'debit' => 5000000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry1)->create(['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => 5000000]);

        // 2. Cash sale (Operating)
        $entry2 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry2)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry2)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 1000000]);

        // 3. Buy equipment (Investing)
        $entry3 = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-03-01', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry3)->create(['account_id' => $fixedAssetAccount->id, 'debit' => 500000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry3)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 500000]);

        // Act & Assert
        livewire(ViewCashFlowStatement::class)
            ->fillForm([
                'startDate' => '2025-01-01',
                'endDate' => '2025-03-31',
            ])
            ->call('generateReport')
            ->assertHasNoFormErrors()
            ->assertSet('reportData.beginningCash', fn ($value) => $value !== null)
            ->assertSet('reportData.endingCash', fn ($value) => $value !== null)
            ->assertSet('reportData.netChangeInCash', fn ($value) => $value !== null);
    });

    test('it validates date range input', function () {
        livewire(ViewCashFlowStatement::class)
            ->fillForm([
                'startDate' => null,
                'endDate' => null,
            ])
            ->call('generateReport')
            ->assertHasFormErrors(['startDate', 'endDate']);
    });

    test('it validates end date is after or equal to start date', function () {
        livewire(ViewCashFlowStatement::class)
            ->fillForm([
                'startDate' => '2025-03-31',
                'endDate' => '2025-01-01',
            ])
            ->call('generateReport')
            ->assertHasFormErrors(['endDate']);
    });

    test('it sets default dates to current fiscal year', function () {
        $startOfYear = Carbon::now()->startOfYear()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

        livewire(ViewCashFlowStatement::class)
            ->assertSet('startDate', $startOfYear)
            ->assertSet('endDate', $endOfMonth);
    });

    test('it displays all three activity sections in report', function () {
        // Arrange
        $journal = Journal::factory()->for($this->company)->create();

        $bankAccount = Account::factory()->for($this->company)->create(['type' => \Kezi\Accounting\Enums\Accounting\AccountType::BankAndCash]);
        $salesAccount = Account::factory()->for($this->company)->create(['type' => \Kezi\Accounting\Enums\Accounting\AccountType::Income]);

        // Simple cash sale
        $entry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-02-10', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($entry)->create(['account_id' => $bankAccount->id, 'debit' => 1000000, 'credit' => 0]);
        JournalEntryLine::factory()->for($entry)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 1000000]);

        // Act & Assert
        livewire(ViewCashFlowStatement::class)
            ->fillForm([
                'startDate' => '2025-01-01',
                'endDate' => '2025-03-31',
            ])
            ->call('generateReport')
            ->assertHasNoFormErrors()
            ->assertSet('reportData.operatingLines', fn ($value) => is_array($value))
            ->assertSet('reportData.investingLines', fn ($value) => is_array($value))
            ->assertSet('reportData.financingLines', fn ($value) => is_array($value));
    });
});
