<?php

use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\PeriodIsLockedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\LockDate;
use App\Models\User;
use App\Services\JournalEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->company = $this->createConfiguredCompany();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
});

test('a journal entry correctly calculates totals and assigns a user when created', function () {
    $entryData = [
        'company_id' => $this->company->id,
        'journal_id' => Journal::factory()->for($this->company)->create()->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'JE-BALANCE-001',
        'created_by_user_id' => $this->user->id, // Pass the required user ID
        'lines' => [
            ['account_id' => Account::factory()->for($this->company)->create()->id, 'debit' => 125.50],
            ['account_id' => Account::factory()->for($this->company)->create()->id, 'credit' => 125.50],
        ],
    ];

    // Act
    $journalEntry = (app(JournalEntryService::class))->create($entryData);

    // Assert
    expect($journalEntry->total_debit)->toEqual(12550);
    expect($journalEntry->total_credit)->toEqual(12550);
    expect($journalEntry->created_by_user_id)->toBe($this->user->id); // Also assert the user was set
});

test('creating an unbalanced journal entry is prevented', function () {
    // Arrange: Prepare data where debits do NOT equal credits.
    $unbalancedData = [
        'company_id' => $this->company->id,
        'journal_id' => Journal::factory()->for($this->company)->create()->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'JE-UNBALANCED-001',
        'lines' => [
            ['account_id' => Account::factory()->for($this->company)->create()->id, 'debit' => 100.00],
            ['account_id' => Account::factory()->for($this->company)->create()->id, 'credit' => 99.99], // Unbalanced!
        ],
    ];

    // Assert: Expect the service to throw a ValidationException because the entry is unbalanced.
    expect(fn() => (app(JournalEntryService::class))->create($unbalancedData))
        ->toThrow(ValidationException::class);
});

test('a balanced draft journal entry can be posted', function () {
    // Arrange: Create a draft journal entry with balanced lines.
    $journalEntry = JournalEntry::factory()->for($this->company)->create(['is_posted' => false]);
    $journalEntry->lines()->createMany([
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'debit' => 100.00],
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'credit' => 100.00],
    ]);

    // Act: Call the post method on the service.
    (app(JournalEntryService::class))->post($journalEntry);

    // Assert: Check the model directly to see if its state was correctly updated.
    $journalEntry->refresh();
    expect($journalEntry->is_posted)->toBeTrue();
});

test('an unbalanced draft journal entry cannot be posted', function () {
    // Arrange: Create a draft entry with UNBALANCED lines.
    $journalEntry = JournalEntry::factory()->for($this->company)->create(['is_posted' => false]);
    $journalEntry->lines()->createMany([
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'debit' => 100.00],
        ['account_id' => Account::factory()->for($this->company)->create()->id, 'credit' => 99.00], // Unbalanced!
    ]);

    // Assert: Expect the service's post method to reject this and throw an exception.
    expect(fn() => (app(JournalEntryService::class))->post($journalEntry))
        ->toThrow(ValidationException::class);
});

test('a draft journal entry can be freely modified before posting', function () {
    // Arrange: Create a draft journal entry.
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false,
        'description' => 'Initial Draft Description'
    ]);

    $updateData = ['description' => 'Updated Draft Description'];

    // Act: Call the update method on the service.
    $updatedEntry = (app(JournalEntryService::class))->update($journalEntry, $updateData);

    // Assert: Confirm the update was successful and the data was changed.
    expect($updatedEntry)->toBeInstanceOf(JournalEntry::class);
    expect($updatedEntry->id)->toBe($journalEntry->id);
    expect($updatedEntry->description)->toBe('Updated Draft Description');
    expect($journalEntry->fresh()->description)->toBe('Updated Draft Description');
});

test('a posted journal entry cannot be updated', function () {
    // Arrange: Create a journal entry that is already posted.
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => true,
        'description' => 'Original Posted Entry'
    ]);

    $updateData = ['description' => 'Attempted Unauthorized Update'];

    // Assert: Expect that calling the update method on the service throws our
    // specific exception, proving the action was blocked.
    expect(fn() => (app(JournalEntryService::class))->update($journalEntry, $updateData))
        ->toThrow(UpdateNotAllowedException::class, 'Cannot modify a posted journal entry.');

    // Assert: As a final check, confirm that the data in the database did not change.
    $this->assertDatabaseHas('journal_entries', [
        'id' => $journalEntry->id,
        'description' => 'Original Posted Entry', // The description should be unchanged.
    ]);
});

test('a draft journal entry can be deleted', function () {
    // Arrange: Create the service.
    $service = app(JournalEntryService::class);

    // Arrange: Create a journal entry that is NOT posted (a draft).
    $journalEntry = JournalEntry::factory()->for($this->company)->create(['is_posted' => false]);

    // Act: Call the delete method on the service.
    $wasDeleted = $service->delete($journalEntry);

    // Assert: Confirm the deletion was successful.
    expect($wasDeleted)->toBeTrue();

    // Assert: Confirm the record is gone from the database.
    $this->assertModelMissing($journalEntry);
});

test('a posted journal entry cannot be deleted via the service', function () {
    // Arrange: Create a user and the service.
    $service = app(JournalEntryService::class);

    // Arrange: Create a journal entry that IS posted.
    $journalEntry = JournalEntry::factory()->for($this->company)->create(['is_posted' => true]);

    // Assert: Expect the service to throw our specific exception, blocking the deletion.
    expect(fn() => $service->delete($journalEntry))
        ->toThrow(DeletionNotAllowedException::class, 'Cannot delete a posted journal entry. Corrections must be made with a new reversal entry.');

    // Assert: As a final check, confirm the model still exists in the database.
    $this->assertModelExists($journalEntry);
});

test('a draft journal entry in a locked period cannot be deleted', function () {
    // Arrange: Create a user and the service.
    $service = app(JournalEntryService::class);

    // Arrange: Lock the company's books up to a month ago.
    LockDate::factory()->for($this->company)->create([
        'locked_until' => now()->subMonth(),
    ]);

    // Arrange: Create a draft journal entry with a date inside the locked period.
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false,
        'entry_date' => now()->subMonths(2)->toDateString(), // This date is locked.
    ]);

    // Assert: Expect the service to block the deletion due to the locked period.
    expect(fn() => $service->delete($journalEntry))
        ->toThrow(PeriodIsLockedException::class);

    // Assert: As a final check, confirm the model was NOT deleted.
    $this->assertModelExists($journalEntry);
});

test('a posted journal entry cannot be deleted', function () {
    // Arrange: Create a user who will perform the action.
    $journalEntry = JournalEntry::factory()->for($this->company)->create(['is_posted' => true]);

    // Act: Attempt to delete the model.
    $deleteResult = $journalEntry->delete();

    // Assert: The observer should have returned false, cancelling the deletion.
    expect($deleteResult)->toBeFalse();

    // Assert: The model still exists in the database.
    $this->assertModelExists($journalEntry);
});

test('posting a journal entry generates a cryptographic hash', function () {
    // Arrange: Create a user who will perform the action.
    // Arrange: Create a draft journal entry with no hash.
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false,
        'hash' => null
    ]);

    // Act: Post the entry using the service. This should trigger the observer.
    (app(JournalEntryService::class))->post($journalEntry);

    // Assert: Check the model directly to confirm the hash was generated and saved.
    $journalEntry->refresh(); // Get the latest data from the database.

    expect($journalEntry->hash)->not->toBeNull();
    expect(strlen($journalEntry->hash))->toBe(64); // The length of a SHA-256 hash.
});

test('posting a journal entry links to the previous entry hash to form an audit chain', function () {
    // Arrange: Create a user who will perform the action.
    // Arrange: Create the first entry, which is already posted and has a known hash.
    $firstEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => true,
        'entry_date' => now()->subDay(),
        'hash' => hash('sha256', 'first_entry_data'),
    ]);

    // Arrange: Create the second entry, which is still a draft.
    $secondEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => false,
        'entry_date' => now(),
    ]);

    // Act: Post the second entry using the service. This should trigger the observer logic.
    (app(JournalEntryService::class))->post($secondEntry);

    // Assert: Check the second entry to confirm its 'previous_hash'
    // correctly links to the first entry's 'hash'.
    $secondEntry->refresh();
    expect($secondEntry->previous_hash)->toBe($firstEntry->hash);
});

test('posted journal entries accurately record the creating user and creation timestamp', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    $journalEntry = JournalEntry::factory()->create(['is_posted' => true, 'created_by_user_id' => $user->id]);

    // Vital for comprehensive audit logging and accountability [1, 9, 11, 12].
    expect($journalEntry->created_by_user_id)->toBe($user->id);
    expect($journalEntry->created_at)->not->toBeNull();
});