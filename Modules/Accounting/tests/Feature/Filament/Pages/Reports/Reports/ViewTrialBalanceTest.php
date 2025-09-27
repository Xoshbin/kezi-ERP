<?php

namespace Modules\Accounting\Tests\Feature\Filament\Pages\Reports;

use App\Enums\Accounting\AccountType;
use App\Filament\Clusters\Accounting\Pages\Reports\ViewTrialBalance;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

test('it can render the trial balance page', function () {
    Livewire::test(ViewTrialBalance::class)
        ->assertSuccessful()
        ->assertSee(__('reports.trial_balance_report'))
        ->assertSee(__('reports.report_parameters'))
        ->assertSee(__('reports.as_of_date'))
        ->assertSee(__('reports.generate_report'));
});

test('it generates a balanced trial balance report', function () {
    // Arrange
    $company = $this->company;
    $journal = Journal::factory()->for($company)->create();
    $asOfDate = Carbon::parse('2025-12-31');

    $bankAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create([
        'code' => '1000',
        'name' => 'Bank Account',
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash,
    ]);
    $salesAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create([
        'code' => '4000',
        'name' => 'Sales Revenue',
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::Income,
    ]);
    $expenseAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create([
        'code' => '5000',
        'name' => 'Office Expenses',
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense,
    ]);

    // Transaction 1: Receive cash for a sale (1,500,000 IQD)
    $entry1 = JournalEntry::factory()->for($company)->for($journal)->create([
        'entry_date' => '2025-06-10',
        'state' => 'posted',
    ]);
    JournalEntryLine::factory()->for($entry1)->create([
        'account_id' => $bankAccount->id,
        'debit' => 1500000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->for($entry1)->create([
        'account_id' => $salesAccount->id,
        'debit' => 0,
        'credit' => 1500000,
    ]);

    // Transaction 2: Pay an expense from the bank (350,000 IQD)
    $entry2 = JournalEntry::factory()->for($company)->for($journal)->create([
        'entry_date' => '2025-07-15',
        'state' => 'posted',
    ]);
    JournalEntryLine::factory()->for($entry2)->create([
        'account_id' => $expenseAccount->id,
        'debit' => 350000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->for($entry2)->create([
        'account_id' => $bankAccount->id,
        'debit' => 0,
        'credit' => 350000,
    ]);

    // Action & Assert
    $component = Livewire::test(ViewTrialBalance::class)
        ->set('asOfDate', $asOfDate->toDateString())
        ->call('generateReport')
        ->assertSuccessful()
        ->assertSee('Bank Account')
        ->assertSee('Sales Revenue')
        ->assertSee('Office Expenses')
        ->assertSee(__('reports.trial_balance_balanced'))
        ->assertSee(__('reports.total'));

    // Check that the report data contains the expected values
    $reportData = $component->get('reportData');
    expect($reportData)->not->toBeNull();
    expect($reportData['isBalanced'])->toBeTrue();
    expect($reportData['reportLines'])->toHaveCount(3);

    // Verify account ordering by code
    expect($reportData['reportLines'][0]['accountCode'])->toBe('1000');
    expect($reportData['reportLines'][1]['accountCode'])->toBe('4000');
    expect($reportData['reportLines'][2]['accountCode'])->toBe('5000');
});

test('it shows unbalanced status when trial balance does not balance', function () {
    // Arrange - Create an intentionally unbalanced entry (this should not happen in real system)
    $company = $this->company;
    $journal = Journal::factory()->for($company)->create();
    $asOfDate = Carbon::parse('2025-12-31');

    $bankAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);

    // Create an unbalanced entry by directly inserting into database
    $entry = JournalEntry::factory()->for($company)->for($journal)->create([
        'entry_date' => '2025-06-10',
        'state' => 'posted',
    ]);
    JournalEntryLine::factory()->for($entry)->create([
        'account_id' => $bankAccount->id,
        'debit' => 1000000,
        'credit' => 0,
    ]);
    // Missing credit side to make it unbalanced

    // Action & Assert
    Livewire::test(ViewTrialBalance::class)
        ->set('asOfDate', $asOfDate->toDateString())
        ->call('generateReport')
        ->assertSuccessful()
        ->assertSee(__('reports.trial_balance_not_balanced'));
});

test('it validates required as of date', function () {
    Livewire::test(ViewTrialBalance::class)
        ->set('asOfDate', '')
        ->call('generateReport')
        ->assertHasErrors(['asOfDate' => 'required']);
});

test('it shows no data message when no account balances exist', function () {
    Livewire::test(ViewTrialBalance::class)
        ->set('asOfDate', Carbon::now()->toDateString())
        ->call('generateReport')
        ->assertSuccessful()
        ->assertSee(__('reports.no_account_balances_found'));
});

test('it excludes draft journal entries from trial balance', function () {
    // Arrange
    $company = $this->company;
    $journal = Journal::factory()->for($company)->create();
    $asOfDate = Carbon::parse('2025-12-31');

    $bankAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
    $salesAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income]);

    // Draft transaction (should be excluded)
    $draftEntry = JournalEntry::factory()->for($company)->for($journal)->create([
        'entry_date' => '2025-06-10',
        'state' => 'draft',
    ]);
    JournalEntryLine::factory()->for($draftEntry)->create([
        'account_id' => $bankAccount->id,
        'debit' => 1000000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->for($draftEntry)->create([
        'account_id' => $salesAccount->id,
        'debit' => 0,
        'credit' => 1000000,
    ]);

    // Action & Assert
    Livewire::test(ViewTrialBalance::class)
        ->set('asOfDate', $asOfDate->toDateString())
        ->call('generateReport')
        ->assertSuccessful()
        ->assertSee(__('reports.no_account_balances_found'));
});

test('it respects the as of date filter', function () {
    // Arrange
    $company = $this->company;
    $journal = Journal::factory()->for($company)->create();
    $asOfDate = Carbon::parse('2025-06-15');

    $bankAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash]);
    $salesAccount = \Modules\Accounting\Models\Account::factory()->for($company)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income]);

    // Transaction before the as of date (should be included)
    $entry1 = JournalEntry::factory()->for($company)->for($journal)->create([
        'entry_date' => '2025-06-10',
        'state' => 'posted',
    ]);
    JournalEntryLine::factory()->for($entry1)->create([
        'account_id' => $bankAccount->id,
        'debit' => 1000000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->for($entry1)->create([
        'account_id' => $salesAccount->id,
        'debit' => 0,
        'credit' => 1000000,
    ]);

    // Transaction after the as of date (should be excluded)
    $entry2 = JournalEntry::factory()->for($company)->for($journal)->create([
        'entry_date' => '2025-06-20',
        'state' => 'posted',
    ]);
    JournalEntryLine::factory()->for($entry2)->create([
        'account_id' => $bankAccount->id,
        'debit' => 500000,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->for($entry2)->create([
        'account_id' => $salesAccount->id,
        'debit' => 0,
        'credit' => 500000,
    ]);

    // Action & Assert
    $component = Livewire::test(ViewTrialBalance::class)
        ->set('asOfDate', $asOfDate->toDateString())
        ->call('generateReport')
        ->assertSuccessful();

    $reportData = $component->get('reportData');
    expect($reportData['reportLines'])->toHaveCount(2);

    // Should only include the first transaction amounts
    $bankLine = collect($reportData['reportLines'])->firstWhere('accountCode', $bankAccount->code);
    expect($bankLine['debitAmount'])->toBe(1000000.0); // 1M IQD, not 1.5M
});
