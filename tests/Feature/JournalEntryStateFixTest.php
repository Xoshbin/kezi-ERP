<?php

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use App\Models\Journal;
use App\Models\Currency;
use App\Models\Company;
use App\Models\User;
use App\Services\JournalEntryService;
use App\Enums\Accounting\JournalEntryState;
use Brick\Money\Money;
use Carbon\Carbon;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('updates journal entry state to posted when posting via service', function () {
    // Arrange: Create a journal entry in draft state
    $company = Company::factory()->create();
    $journal = Journal::factory()->create(['company_id' => $company->id]);
    $currency = Currency::factory()->create(['code' => 'USD']);
    $bankAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Test Bank Account',
    ]);
    $equityAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Test Equity Account',
    ]);

    $amount = Money::of(1000, $currency->code);

    $journalEntry = JournalEntry::create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $currency->id,
        'entry_date' => Carbon::today(),
        'reference' => 'TEST-STATE-001',
        'description' => 'Test journal entry for state fix',
        'state' => JournalEntryState::Draft,
        'is_posted' => false,
        'total_debit' => $amount,
        'total_credit' => $amount,
    ]);

    // Create journal entry lines
    JournalEntryLine::create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $bankAccount->id,
        'debit' => $amount,
        'credit' => Money::of(0, $currency->code),
        'description' => 'Test debit line',
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $equityAccount->id,
        'debit' => Money::of(0, $currency->code),
        'credit' => $amount,
        'description' => 'Test credit line',
    ]);

    // Verify initial state
    expect($journalEntry->state)->toBe(JournalEntryState::Draft);
    expect($journalEntry->is_posted)->toBeFalse();

    // Act: Post the journal entry using the service
    $service = app(JournalEntryService::class);
    $result = $service->post($journalEntry);

    // Assert: Verify the posting was successful
    expect($result)->toBeTrue();

    // Refresh the model to get the latest state from database
    $journalEntry->refresh();

    // Assert: Verify both state and is_posted are correctly updated
    expect($journalEntry->state)->toBe(JournalEntryState::Posted)
        ->and($journalEntry->is_posted)->toBeTrue();
});

it('does not allow posting an already posted journal entry', function () {
    // Arrange: Create a journal entry that's already posted
    $company = Company::factory()->create();
    $journal = Journal::factory()->create(['company_id' => $company->id]);
    $currency = Currency::factory()->create(['code' => 'EUR']);

    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $currency->id,
        'state' => JournalEntryState::Posted,
        'is_posted' => true,
    ]);

    // Act: Try to post the already posted journal entry
    $service = app(JournalEntryService::class);
    $result = $service->post($journalEntry);

    // Assert: Verify the service returns true (already posted, so "success")
    expect($result)->toBeTrue();
});
