<?php

namespace Tests\Feature\Filament\Reports;

use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalEntryState;
use App\Filament\Clusters\Accounting\Clusters\AccountingReports\Pages\Reports\ViewProfitAndLoss;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('ViewProfitAndLoss Filament Page', function () {
    test('it can render the page', function () {
        livewire(ViewProfitAndLoss::class)
            ->assertSuccessful();
    });

    test('it can generate a profit and loss report', function () {
        // Arrange - Create test data
        $currency = $this->company->currency->code;
        $journal = Journal::factory()->for($this->company)->create();

        $salesAccount = Account::factory()->for($this->company)->create(['type' => AccountType::Income]);
        $expenseAccount = Account::factory()->for($this->company)->create(['type' => AccountType::Expense]);
        $receivableAccount = Account::factory()->for($this->company)->create(['type' => AccountType::Receivable]);
        $bankAccount = Account::factory()->for($this->company)->create(['type' => AccountType::BankAndCash]);

        // Create a sale transaction
        $salesEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-15', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($salesEntry)->create(['account_id' => $receivableAccount->id, 'debit' => 500000, 'credit' => 0]);
        JournalEntryLine::factory()->for($salesEntry)->create(['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 500000]);

        // Create an expense transaction
        $expenseEntry = JournalEntry::factory()->for($this->company)->for($journal)
            ->create(['entry_date' => '2025-01-20', 'state' => JournalEntryState::Posted]);
        JournalEntryLine::factory()->for($expenseEntry)->create(['account_id' => $expenseAccount->id, 'debit' => 200000, 'credit' => 0]);
        JournalEntryLine::factory()->for($expenseEntry)->create(['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 200000]);

        // Act & Assert
        livewire(ViewProfitAndLoss::class)
            ->fillForm([
                'startDate' => '2025-01-01',
                'endDate' => '2025-01-31',
            ])
            ->call('generateReport')
            ->assertHasNoFormErrors()
            ->assertSet('reportData.netIncomeAmount', 300000.0) // 300,000 IQD
            ->assertSet('reportData.isNetLoss', false)
            ->assertCount('reportData.revenueLines', 1)
            ->assertCount('reportData.expenseLines', 1);
    });

    test('it validates date range input', function () {
        livewire(ViewProfitAndLoss::class)
            ->fillForm([
                'startDate' => '2025-01-31',
                'endDate' => '2025-01-01', // End date before start date
            ])
            ->call('generateReport')
            ->assertHasFormErrors(['endDate']);
    });

    test('it requires both start and end dates', function () {
        livewire(ViewProfitAndLoss::class)
            ->fillForm([
                'startDate' => null,
                'endDate' => null,
            ])
            ->call('generateReport')
            ->assertHasFormErrors(['startDate', 'endDate']);
    });

    test('it sets default date range to current month', function () {
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

        livewire(ViewProfitAndLoss::class)
            ->assertSet('startDate', $startOfMonth)
            ->assertSet('endDate', $endOfMonth);
    });
});
