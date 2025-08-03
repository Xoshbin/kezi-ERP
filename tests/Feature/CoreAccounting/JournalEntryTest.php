<?php

use App\Models\User;
use Brick\Money\Money;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\LockDate;
use Tests\Traits\MocksTime;
use App\Models\JournalEntry;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithUnlockedPeriod;
use App\Services\JournalEntryService;
use Tests\Traits\WithConfiguredCompany;
use App\Exceptions\PeriodIsLockedException;
use App\Exceptions\UpdateNotAllowedException;
use Illuminate\Validation\ValidationException;
use App\Exceptions\DeletionNotAllowedException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

test('a journal entry correctly calculates totals and assigns a user when created', function () {
    $currencyCode = $this->company->currency->code;
    $entryData = [
        'company_id' => $this->company->id,
        'journal_id' => Journal::factory()->for($this->company)->create()->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'JE-BALANCE-001',
        'created_by_user_id' => $this->user->id,
        'currency_id' => $this->company->currency_id,
        'lines' => [
            ['account_id' => Account::factory()->for($this->company)->create()->id, 'debit' => Money::of('125.50', $currencyCode), 'credit' => Money::of(0, $currencyCode)],
            ['account_id' => Account::factory()->for($this->company)->create()->id, 'credit' => Money::of('125.50', $currencyCode), 'debit' => Money::of(0, $currencyCode)],
        ],
    ];

    $journalEntry = (app(JournalEntryService::class))->create($entryData);

    $expectedAmount = Money::of('125.50', $currencyCode);
    expect($journalEntry->total_debit->isEqualTo($expectedAmount))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedAmount))->toBeTrue();
    expect($journalEntry->created_by_user_id)->toBe($this->user->id);
});

test('creating an unbalanced journal entry is prevented', function () {
    $currencyCode = $this->company->currency->code;
    $unbalancedData = [
        'company_id' => $this->company->id,
        'journal_id' => Journal::factory()->for($this->company)->create()->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'JE-UNBALANCED-001',
        'currency_id' => $this->company->currency_id,
        'lines' => [
            ['account_id' => Account::factory()->for($this->company)->create()->id, 'debit' => Money::of('100.00', $currencyCode), 'credit' => Money::of(0, $currencyCode)],
            ['account_id' => Account::factory()->for($this->company)->create()->id, 'credit' => Money::of('99.99', $currencyCode), 'debit' => Money::of(0, $currencyCode)],
        ],
    ];

    expect(fn() => (app(JournalEntryService::class))->create($unbalancedData))
        ->toThrow(ValidationException::class);
});

test('a balanced draft journal entry can be posted', function () {
    $currencyCode = $this->company->currency->code;
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false,
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);
    // MODIFIED: Pass currency_id directly to the lines.
    $journalEntry->lines()->createMany([
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'debit' => Money::of('100.00', $currencyCode), 'credit' => Money::of(0, $currencyCode), 'currency_id' => $journalEntry->currency_id],
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'credit' => Money::of('100.00', $currencyCode), 'debit' => Money::of(0, $currencyCode), 'currency_id' => $journalEntry->currency_id],
    ]);

    (app(JournalEntryService::class))->post($journalEntry);

    $journalEntry->refresh();
    expect($journalEntry->is_posted)->toBeTrue();
});

test('an unbalanced draft journal entry cannot be posted', function () {
    $currencyCode = $this->company->currency->code;
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false,
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);
    // MODIFIED: Pass currency_id directly to the lines.
    $journalEntry->lines()->createMany([
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'debit' => Money::of('100.00', $currencyCode), 'credit' => Money::of(0, $currencyCode), 'currency_id' => $journalEntry->currency_id],
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'credit' => Money::of('99.00', $currencyCode), 'debit' => Money::of(0, $currencyCode), 'currency_id' => $journalEntry->currency_id],
    ]);

    expect(fn() => (app(JournalEntryService::class))->post($journalEntry))
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
        ->toThrow(DeletionNotAllowedException::class, 'Cannot delete a posted journal entry. Corrections must be made with a new reversal entry.');

    $this->assertModelExists($journalEntry);
});

test('a draft journal entry in a locked period cannot be deleted', function () {
    $service = app(JournalEntryService::class);
    LockDate::factory()->for($this->company)->create(['locked_until' => now()->subMonth()]);
    $currencyCode = $this->company->currency->code;
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false,
        'entry_date' => now()->subMonths(2)->toDateString(),
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);

    expect(fn() => $service->delete($journalEntry))
        ->toThrow(PeriodIsLockedException::class);

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
    $service = app(JournalEntryService::class);
    $currencyCode = $this->company->currency->code;

    // 1. Create and correctly post the first entry so it gets a real hash.
    $firstEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false, // Start as a draft
        'entry_date' => now()->subDay(),
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);
    // Add balanced lines so it's postable
    $firstEntry->lines()->createMany([
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'debit' => Money::of(100, $currencyCode), 'credit' => Money::of(0, $currencyCode), 'currency_id' => $firstEntry->currency_id],
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'credit' => Money::of(100, $currencyCode), 'debit' => Money::of(0, $currencyCode), 'currency_id' => $firstEntry->currency_id],
    ]);
    $service->post($firstEntry);

    // 2. Create the second entry as a draft.
    $secondEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false,
        'entry_date' => now(),
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);
    // Also give it balanced lines
    $secondEntry->lines()->createMany([
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'debit' => Money::of(200, $currencyCode), 'credit' => Money::of(0, $currencyCode), 'currency_id' => $secondEntry->currency_id],
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'credit' => Money::of(200, $currencyCode), 'debit' => Money::of(0, $currencyCode), 'currency_id' => $secondEntry->currency_id],
    ]);


    // 3. Post the second entry, which triggers the linking logic.
    $service->post($secondEntry);

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
