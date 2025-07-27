<?php

use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountService;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->company = $this->createConfiguredCompany();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
});

test('creating an account with a duplicate code for the same company is prevented', function () {
    // Arrange: Create the first account with code '1000'.
    Account::factory()->for($this->company)->create(['code' => '1000']);

    // Arrange: Prepare the data for the second account, which is a duplicate.
    $duplicateAccountData = [
        'company_id' => $this->company->id,
        'code' => '1000', // The duplicate code
        'name' => 'Duplicate Cash Account',
        'type' => 'Asset',
    ];

    // Arrange: Get the service that contains your business rules.
    $accountService = app(AccountService::class);

    // Assert: Expect that trying to create the duplicate account will fail
    // with a ValidationException. This proves your backend rule works.
    expect(fn() => $accountService->create($duplicateAccountData))
        ->toThrow(ValidationException::class);
});

test('an account with existing transactions is marked as deprecated instead of being deleted', function () {
    // Arrange: Create an account and link a transaction to it.
    $account = Account::factory()->for($this->company)->create();
    $journal = Journal::factory()->for($this->company)->create();
    JournalEntry::factory()->for($this->company)->for($journal)->create()->lines()->create(['account_id' => $account->id, 'debit' => 100]);

    // Act: Attempt to delete the account. We expect our Observer to intercept this.
    // The delete() method should return false because the Observer cancels the operation.
    $deleteResult = $account->delete();

    // Assert: The deletion was cancelled.
    expect($deleteResult)->toBeFalse();

    // Assert: The account still exists and is now deprecated.
    // This is the only database check you need for this test.
    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'is_deprecated' => true,
    ]);
});

test('a deprecated account cannot be used for new financial transactions', function () {
    // Arrange: Create the necessary accounts.
    $deprecatedAccount = Account::factory()->for($this->company)->create(['is_deprecated' => true]);
    $activeAccount = Account::factory()->for($this->company)->create();

    // Arrange: Prepare the data for a journal entry that attempts to use the deprecated account.
    $journalEntryData = [
        'company_id' => $this->company->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'INVALID-USE-DEPRECATED',
        'lines' => [
            // This line uses the deprecated account, which should be rejected.
            ['account_id' => $deprecatedAccount->id, 'debit' => 100],
            // This line is valid.
            ['account_id' => $activeAccount->id, 'credit' => 100],
        ],
    ];

    // Arrange: Instantiate the service that contains the business logic.
    $journalEntryService = app(\App\Services\JournalEntryService::class);

    // Assert: Expect the service to throw a specific, clear exception when it detects
    // the use of a deprecated account. This confirms the backend rule is enforced.
    expect(fn() => $journalEntryService->create($journalEntryData))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

test('creating a journal with an existing short code for the same company is prevented', function () {
    // Arrange: Create the initial journal.
    Journal::factory()->for($this->company)->create(['short_code' => 'INV']);

    // Arrange: Prepare data for the duplicate journal.
    $duplicateData = [
        'company_id' => $this->company->id,
        'name' => 'Duplicate Sales Journal',
        'type' => 'Sale',
        'short_code' => 'INV', // The duplicate code
    ];

    // Arrange: Instantiate the service that holds your logic.
    $journalService = app(JournalService::class);

    // Assert: Expect the service to throw a ValidationException when it
    // detects the duplicate short code for the given company.
    expect(fn() => $journalService->create($duplicateData))
        ->toThrow(ValidationException::class);
});