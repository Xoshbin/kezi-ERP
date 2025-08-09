<?php

use App\Models\User;
use Brick\Money\Money;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Services\AccountService;
use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Services\JournalService;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithConfiguredCompany;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

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
    $currencyCode = $this->company->currency->code;

    // MODIFIED: Create the journal entry with correct Money objects for its totals.
    $journalEntry = JournalEntry::factory()
        ->for($this->company)
        ->for($journal)
        ->create([
            'total_debit' => Money::of(100, $currencyCode),
            'total_credit' => Money::of(100, $currencyCode), // Ensure the entry is balanced from the start.
        ]);

    // MODIFIED: Create the lines associated with the entry, ensuring it's balanced.
    $balancingAccount = Account::factory()->for($this->company)->create();
    $journalEntry->lines()->create([
        'account_id' => $account->id,
        'debit' => Money::of(100, $currencyCode),
        'credit' => Money::of(0, $currencyCode),
    ]);
    $journalEntry->lines()->create([
        'account_id' => $balancingAccount->id,
        'credit' => Money::of(100, $currencyCode),
        'debit' => Money::of(0, $currencyCode),
    ]);


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
    $currencyCode = $this->company->currency->code;
    $lineDTOs = [
        new CreateJournalEntryLineDTO(
            account_id: $deprecatedAccount->id,
            debit: Money::of(100, $currencyCode),
            credit: Money::of(0, $currencyCode),
            description: 'Deprecated account line',
            partner_id: null,
            analytic_account_id: null,
        ),
        new CreateJournalEntryLineDTO(
            account_id: $activeAccount->id,
            debit: Money::of(0, $currencyCode),
            credit: Money::of(100, $currencyCode),
            description: 'Active account line',
            partner_id: null,
            analytic_account_id: null,
        ),
    ];

    $journalEntryDTO = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: Journal::factory()->for($this->company)->create()->id,
        currency_id: $this->company->currency_id,
        entry_date: now()->toDateString(),
        reference: 'INVALID-USE-DEPRECATED',
        description: 'Attempt to use deprecated account',
        created_by_user_id: $this->user->id,
        is_posted: true,
        lines: $lineDTOs,
    );

    // Arrange: Instantiate the action that contains the business logic.
    $createJournalEntryAction = app(CreateJournalEntryAction::class);

    // Assert: Expect the action to throw a specific, clear exception when it detects
    // the use of a deprecated account. This confirms the backend rule is enforced.
    expect(fn() => $createJournalEntryAction->execute($journalEntryDTO))
        ->toThrow(ValidationException::class);
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
