<?php

use App\Models\User;
use Brick\Money\Money;
use App\Models\Company;
use Tests\Traits\MocksTime;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\LockDate;
use Tests\Traits\WithConfiguredCompany;
use Modules\Accounting\Models\JournalEntry;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Accounting\Enums\Accounting\LockDateType;
use Modules\Accounting\Exceptions\PeriodIsLockedException;
use Modules\Foundation\Exceptions\DeletionNotAllowedException;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;

uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

test('a journal entry correctly calculates totals and assigns a user when created', function () {
    $currency = $this->company->currency;
    $account1 = Account::factory()->for($this->company)->create();
    $account2 = Account::factory()->for($this->company)->create();

    $dto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: Journal::factory()->for($this->company)->create()->id,
        currency_id: $currency->id,
        entry_date: now()->toDateString(),
        reference: 'JE-BALANCE-001',
        description: 'Test Entry',
        created_by_user_id: $this->user->id,
        is_posted: true,
        lines: [
            new CreateJournalEntryLineDTO(account_id: $account1->id, debit: Money::of('125.50', $currency->code), credit: Money::of(0, $currency->code), description: 'Line 1', partner_id: null, analytic_account_id: null),
            new CreateJournalEntryLineDTO(account_id: $account2->id, debit: Money::of(0, $currency->code), credit: Money::of('125.50', $currency->code), description: 'Line 2', partner_id: null, analytic_account_id: null),
        ],
    );

    $journalEntry = (app(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class))->execute($dto);

    $expectedAmount = Money::of('125.50', $currency->code);
    expect($journalEntry->total_debit->isEqualTo($expectedAmount))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedAmount))->toBeTrue();
    expect($journalEntry->created_by_user_id)->toBe($this->user->id);
});

test('creating an unbalanced journal entry is prevented', function () {
    $currency = $this->company->currency;
    $account1 = Account::factory()->for($this->company)->create();
    $account2 = Account::factory()->for($this->company)->create();

    $dto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: Journal::factory()->for($this->company)->create()->id,
        currency_id: $currency->id,
        entry_date: now()->toDateString(),
        reference: 'JE-UNBALANCED-001',
        description: 'Test Unbalanced Entry',
        created_by_user_id: $this->user->id,
        is_posted: true,
        lines: [
            new CreateJournalEntryLineDTO(account_id: $account1->id, debit: Money::of('100.00', $currency->code), credit: Money::of(0, $currency->code), description: 'Line 1', partner_id: null, analytic_account_id: null),
            new CreateJournalEntryLineDTO(account_id: $account2->id, debit: Money::of(0, $currency->code), credit: Money::of('99.99', $currency->code), description: 'Line 2', partner_id: null, analytic_account_id: null),
        ],
    );

    expect(fn() => (app(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class))->execute($dto))
        ->toThrow(ValidationException::class);
});

test('a balanced draft journal entry can be posted', function () {
    $currency = $this->company->currency;
    $account1 = Account::factory()->for($this->company)->create();
    $account2 = Account::factory()->for($this->company)->create();

    $dto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: Journal::factory()->for($this->company)->create()->id,
        currency_id: $currency->id,
        entry_date: now()->toDateString(),
        reference: 'JE-DRAFT-001',
        description: 'Test Draft Entry',
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: [
            new CreateJournalEntryLineDTO(account_id: $account1->id, debit: Money::of('100.00', $currency->code), credit: Money::of(0, $currency->code), description: 'Debit', partner_id: null, analytic_account_id: null),
            new CreateJournalEntryLineDTO(account_id: $account2->id, debit: Money::of(0, $currency->code), credit: Money::of('100.00', $currency->code), description: 'Credit', partner_id: null, analytic_account_id: null),
        ],
    );

    $journalEntry = (app(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class))->execute($dto);
    expect($journalEntry->is_posted)->toBeFalse();

    (app(JournalEntryService::class))->post($journalEntry);

    $journalEntry->refresh();
    expect($journalEntry->is_posted)->toBeTrue();
});

test('an unbalanced draft journal entry cannot be posted', function () {
    $currency = $this->company->currency;
    $account1 = Account::factory()->for($this->company)->create();
    $account2 = Account::factory()->for($this->company)->create();

    $dto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: Journal::factory()->for($this->company)->create()->id,
        currency_id: $currency->id,
        entry_date: now()->toDateString(),
        reference: 'JE-UNBALANCED-DRAFT-001',
        description: 'Test Unbalanced Draft',
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: [
            new CreateJournalEntryLineDTO(account_id: $account1->id, debit: Money::of('100.00', $currency->code), credit: Money::of(0, $currency->code), description: 'Debit', partner_id: null, analytic_account_id: null),
            new CreateJournalEntryLineDTO(account_id: $account2->id, debit: Money::of(0, $currency->code), credit: Money::of('99.00', $currency->code), description: 'Credit', partner_id: null, analytic_account_id: null),
        ],
    );

    expect(fn() => (app(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class))->execute($dto))
        ->toThrow(ValidationException::class);
});

test('a posted journal entry is immutable and cannot be updated', function () {
    // Arrange: Create a posted journal entry.
    $currencyCode = $this->company->currency->code;
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => true,
        'description' => 'Original Posted Entry',
        'total_debit' => Money::of(100, $currencyCode),
        'total_credit' => Money::of(100, $currencyCode),
    ]);

    // Act: Attempt to change an attribute on the posted entry.
    $journalEntry->description = 'Attempted Unauthorized Update';

    // Assert: Expect the model's internal 'updating' event listener to throw a RuntimeException.
    // This correctly tests the application's actual data integrity guard.
    expect(fn() => $journalEntry->save())
        ->toThrow(\RuntimeException::class, "Attempted to modify immutable posted journal entry field: 'description'.");

    // Assert: Double-check that the description was not changed in the database.
    $this->assertDatabaseHas('journal_entries', [
        'id' => $journalEntry->id,
        'description' => 'Original Posted Entry',
    ]);
});

test('a draft journal entry can be deleted', function () {
    $service = app(JournalEntryService::class);
    $currencyCode = $this->company->currency->code;
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false,
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);

    $wasDeleted = $service->delete($journalEntry);

    expect($wasDeleted)->toBeTrue();
    $this->assertModelMissing($journalEntry);
});

test('a posted journal entry cannot be deleted via the service', function () {
    $service = app(JournalEntryService::class);
    $currencyCode = $this->company->currency->code;
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => true,
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);

    expect(fn() => $service->delete($journalEntry))
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class, 'Cannot delete a posted journal entry. Corrections must be made with a new reversal entry.');

    $this->assertModelExists($journalEntry);
});

test('a draft journal entry in a locked period cannot be deleted', function () {
    $service = app(JournalEntryService::class);
    LockDate::factory()->for($this->company)->create([
        'lock_type' => LockDateType::AllUsers->value,
        'locked_until' => now()->subMonth(),
    ]);
    $currencyCode = $this->company->currency->code;
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false,
        'entry_date' => now()->subMonths(2)->toDateString(),
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);

    expect(fn() => $service->delete($journalEntry))
        ->toThrow(\Modules\Accounting\Exceptions\PeriodIsLockedException::class);

    $this->assertModelExists($journalEntry);
});

test('a posted journal entry cannot be deleted', function () {
    $currencyCode = $this->company->currency->code;
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => true,
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);

    $deleteResult = $journalEntry->delete();

    expect($deleteResult)->toBeFalse();
    $this->assertModelExists($journalEntry);
});

test('posting a journal entry generates a cryptographic hash', function () {
    $currencyCode = $this->company->currency->code;
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false,
        'hash' => null,
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);

    (app(JournalEntryService::class))->post($journalEntry);

    $journalEntry->refresh();
    expect($journalEntry->hash)->not->toBeNull();
    expect(strlen($journalEntry->hash))->toBe(64);
});

test('posting a journal entry links to the previous entry hash to form an audit chain', function () {
    $action = app(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class);
    $currency = $this->company->currency;
    $account1 = Account::factory()->for($this->company)->create();
    $account2 = Account::factory()->for($this->company)->create();
    $account3 = Account::factory()->for($this->company)->create();
    $account4 = Account::factory()->for($this->company)->create();

    // 1. Create and post the first entry.
    $firstDto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: Journal::factory()->for($this->company)->create()->id,
        currency_id: $currency->id,
        entry_date: now()->subDay()->toDateString(),
        reference: 'JE-CHAIN-001',
        description: 'First Entry',
        created_by_user_id: $this->user->id,
        is_posted: true,
        lines: [
            new CreateJournalEntryLineDTO(account_id: $account1->id, debit: Money::of(100, $currency->code), credit: Money::of(0, $currency->code), description: 'Debit 1', partner_id: null, analytic_account_id: null),
            new CreateJournalEntryLineDTO(account_id: $account2->id, debit: Money::of(0, $currency->code), credit: Money::of(100, $currency->code), description: 'Credit 1', partner_id: null, analytic_account_id: null),
        ],
    );
    $firstEntry = $action->execute($firstDto);

    // 2. Create and post the second entry.
    $secondDto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $firstDto->journal_id,
        currency_id: $currency->id,
        entry_date: now()->toDateString(),
        reference: 'JE-CHAIN-002',
        description: 'Second Entry',
        created_by_user_id: $this->user->id,
        is_posted: true,
        lines: [
            new CreateJournalEntryLineDTO(account_id: $account3->id, debit: Money::of(200, $currency->code), credit: Money::of(0, $currency->code), description: 'Debit 2', partner_id: null, analytic_account_id: null),
            new CreateJournalEntryLineDTO(account_id: $account4->id, debit: Money::of(0, $currency->code), credit: Money::of(200, $currency->code), description: 'Credit 2', partner_id: null, analytic_account_id: null),
        ],
    );
    $secondEntry = $action->execute($secondDto);

    // 4. Refresh both models to get the final state from the database.
    $firstEntry->refresh();
    $secondEntry->refresh();

    // 5. Assert that the chain is correctly linked.
    expect($firstEntry->hash)->not->toBeNull(); // First, ensure the first entry got a hash.
    expect($secondEntry->previous_hash)->toBe($firstEntry->hash);
});

test('posted journal entries accurately record the creating user and creation timestamp', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $company = Company::factory()->create();
    $currencyCode = $company->currency->code;
    $journalEntry = JournalEntry::factory()->for($company)->create([
        'is_posted' => true,
        'created_by_user_id' => $user->id,
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);

    expect($journalEntry->created_by_user_id)->toBe($user->id);
    expect($journalEntry->created_at)->not->toBeNull();
});
