<?php

use App\Exceptions\AccountIsDeprecatedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\AnalyticAccount;
use App\Models\Company;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\LockDate;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\AdjustmentDocument;
use App\Services\AccountService;
use App\Services\CompanyService;
use App\Services\CurrencyService;
use App\Services\JournalEntryService;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exceptions\DeletionNotAllowedException; // A custom exception you should create
use Illuminate\Validation\ValidationException; // The exception thrown by Laravel's validator

uses(RefreshDatabase::class);

test('a company with existing financial records cannot be deleted', function () {
    // Arrange: Create a company and a dependent financial record.
    $company = Company::factory()->create();
    Account::factory()->for($company)->create();

    // Assert: Expect that our business logic will throw a specific,
    // custom exception when this rule is violated.
    // This is much cleaner than checking for an HTTP status code.
    expect(fn() => $company->delete())
        ->toThrow(DeletionNotAllowedException::class, 'Cannot delete company with associated financial records.');

    // Act & Assert: Double-check that the company was NOT removed from the database.
    // This confirms the deletion was truly prevented.
    $this->assertModelExists($company);
})->only();

test('a user is correctly related to their company for accounting contexts', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    // Verifies the structural integrity crucial for multi-company accounting.
    expect($user->company->id)->toBe($company->id);
})->only();

test('duplicate tax ID for a company in the same fiscal country is prevented', function () {
    // Arrange: Create the first company that sets the baseline for the unique rule.
    Company::factory()->create(['tax_id' => 'VAT123', 'fiscal_country' => 'IQ']);

    // Arrange: Prepare the data for the second, duplicate company.
    $duplicateCompanyData = [
        'name' => 'Duplicate Tax ID Company',
        'tax_id' => 'VAT123', // Same tax_id
        'fiscal_country' => 'IQ', // Same country
        'currency_id' => Currency::factory()->create()->id,
    ];

    // Arrange: Instantiate the service that contains our business logic.
    $companyService = new CompanyService();

    // Assert: We expect that calling the service's create method with duplicate data
    // will fail validation and throw Laravel's standard ValidationException.
    expect(fn() => $companyService->create($duplicateCompanyData))
        ->toThrow(ValidationException::class);
})->only();

test('creating a currency with an existing code is prevented', function () {
    // Arrange: Create the initial currency.
    Currency::factory()->create(['code' => 'IQD']);

    // Arrange: Prepare the data for the duplicate currency.
    $duplicateData = [
        'code' => 'IQD', // The duplicate code
        'name' => 'Iraqi Dinar Duplicate',
        'symbol' => 'د.ع',
        'exchange_rate' => 1.0,
    ];

    // Arrange: Instantiate the service that holds the creation logic.
    $currencyService = new CurrencyService();

    // Assert: Expect the service to throw a ValidationException when trying to create
    // the duplicate record, proving the business rule is enforced.
    expect(fn() => $currencyService->create($duplicateData))
        ->toThrow(ValidationException::class);
})->only();

test('a partner record is soft-deleted to preserve historical transaction context', function () {
    $partner = Partner::factory()->create();
    $partner->delete();

    // Partners, as non-financial records, should be soft-deleted to maintain auditability [2-5].
    $this->assertSoftDeleted($partner);
    expect(Partner::find($partner->id))->toBeNull(); // Verifies default query behavior
})->only();

test('a soft-deleted partner can be retrieved using "withTrashed" for historical reporting', function () {
    $partner = Partner::factory()->create();
    $partner->delete();

    // Ensures that historical data linked to soft-deleted entities is still accessible [2-5].
    expect(Partner::withTrashed()->find($partner->id))->not->toBeNull();
})->only();

test('a product record is soft-deleted to preserve its history and linkages', function () {
    $product = Product::factory()->create();
    $product->delete();

    // Products, like partners, are non-financial and subject to soft deletion principles [2-5].
    $this->assertSoftDeleted($product);
    expect(Product::find($product->id))->toBeNull();
})->only();

test('a soft-deleted product can be retrieved with "withTrashed" for historical analysis', function () {
    $product = Product::factory()->create();
    $product->delete();

    // Verifies the ability to access product history even after deactivation [2-5].
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
})->only();

test('a product is correctly linked to its default income and expense general ledger accounts', function () {
    $incomeAccount = Account::factory()->create(['type' => 'Income']);
    $expenseAccount = Account::factory()->create(['type' => 'Expense']);
    $product = Product::factory()->create([
        'income_account_id' => $incomeAccount->id,
        'expense_account_id' => $expenseAccount->id,
    ]);

    // Ensures proper accounting categorization for product sales and purchases [3, 5].
    expect($product->incomeAccount->id)->toBe($incomeAccount->id);
    expect($product->expenseAccount->id)->toBe($expenseAccount->id);
})->only();

test('a tax is correctly linked to its designated general ledger tax account', function () {
    $taxAccount = Account::factory()->create(['type' => 'Liability']); // e.g., VAT Payable
    $tax = Tax::factory()->create(['tax_account_id' => $taxAccount->id]);

    // Critical for accurate tax reporting and balance sheet presentation [3, 5].
    expect($tax->taxAccount->id)->toBe($taxAccount->id);
})->only();

test('creating an account with a duplicate code for the same company is prevented', function () {
    // Arrange: Create a company and the first account with code '1000'.
    $company = Company::factory()->create();
    Account::factory()->for($company)->create(['code' => '1000']);

    // Arrange: Prepare the data for the second account, which is a duplicate.
    $duplicateAccountData = [
        'company_id' => $company->id,
        'code' => '1000', // The duplicate code
        'name' => 'Duplicate Cash Account',
        'type' => 'Asset',
    ];

    // Arrange: Get the service that contains your business rules.
    $accountService = new AccountService();

    // Assert: Expect that trying to create the duplicate account will fail
    // with a ValidationException. This proves your backend rule works.
    expect(fn() => $accountService->create($duplicateAccountData))
        ->toThrow(ValidationException::class);
})->only();

test('an account with existing transactions is marked as deprecated instead of being deleted', function () {
    // Arrange: Create an account and link a transaction to it.
    $account = Account::factory()->create();
    JournalEntry::factory()->create()->lines()->create(['account_id' => $account->id, 'debit' => 100]);

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
})->only();

test('a deprecated account cannot be used for new financial transactions', function () {
    // Arrange: Create a company and the necessary accounts.
    $company = Company::factory()->create();
    $deprecatedAccount = Account::factory()->for($company)->create(['is_deprecated' => true]);
    $activeAccount = Account::factory()->for($company)->create();

    // Arrange: Prepare the data for a journal entry that attempts to use the deprecated account.
    $journalEntryData = [
        'company_id' => $company->id,
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
    $journalEntryService = new JournalEntryService();

    // Assert: Expect the service to throw a specific, clear exception when it detects
    // the use of a deprecated account. This confirms the backend rule is enforced.
    expect(fn() => $journalEntryService->create($journalEntryData))
        ->toThrow(Illuminate\Validation\ValidationException::class);
})->only();

test('creating a journal with an existing short code for the same company is prevented', function () {
    // Arrange: Create a company and the initial journal.
    $company = Company::factory()->create();
    Journal::factory()->for($company)->create(['short_code' => 'INV']);

    // Arrange: Prepare data for the duplicate journal.
    $duplicateData = [
        'company_id' => $company->id,
        'name' => 'Duplicate Sales Journal',
        'type' => 'Sale',
        'short_code' => 'INV', // The duplicate code
    ];

    // Arrange: Instantiate the service that holds your logic.
    $journalService = new JournalService();

    // Assert: Expect the service to throw a ValidationException when it
    // detects the duplicate short code for the given company.
    expect(fn() => $journalService->create($duplicateData))
        ->toThrow(ValidationException::class);
})->only();

test('a journal entry correctly calculates totals and assigns a user when created', function () {
    // Arrange: Create a user who will be the creator of the entry
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $entryData = [
        'company_id' => $company->id,
        'journal_id' => Journal::factory()->for($company)->create()->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'JE-BALANCE-001',
        'created_by_user_id' => $user->id, // Pass the required user ID
        'lines' => [
            ['account_id' => Account::factory()->for($company)->create()->id, 'debit' => 125.50],
            ['account_id' => Account::factory()->for($company)->create()->id, 'credit' => 125.50],
        ],
    ];

    // Act
    $journalEntry = (new JournalEntryService())->create($entryData);

    // Assert
    expect($journalEntry->total_debit)->toEqual('125.50');
    expect($journalEntry->total_credit)->toEqual('125.50');
    expect($journalEntry->created_by_user_id)->toBe($user->id); // Also assert the user was set
})->only();

test('creating an unbalanced journal entry is prevented', function () {
    // Arrange: Prepare data where debits do NOT equal credits.
    $company = Company::factory()->create();
    $unbalancedData = [
        'company_id' => $company->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'JE-UNBALANCED-001',
        'lines' => [
            ['account_id' => Account::factory()->for($company)->create()->id, 'debit' => 100.00],
            ['account_id' => Account::factory()->for($company)->create()->id, 'credit' => 99.99], // Unbalanced!
        ],
    ];

    // Assert: Expect the service to throw a ValidationException because the entry is unbalanced.
    expect(fn() => (new JournalEntryService())->create($unbalancedData))
        ->toThrow(ValidationException::class);
})->only();

test('a balanced draft journal entry can be posted', function () {
    // Arrange: Create a draft journal entry with balanced lines.
    $journalEntry = JournalEntry::factory()->create(['is_posted' => false]);
    $journalEntry->lines()->createMany([
        ['account_id' => Account::factory()->create()->id, 'debit' => 100.00],
        ['account_id' => Account::factory()->create()->id, 'credit' => 100.00],
    ]);

    // Act: Call the post method on the service.
    (new JournalEntryService())->post($journalEntry);

    // Assert: Check the model directly to see if its state was correctly updated.
    $journalEntry->refresh();
    expect($journalEntry->is_posted)->toBeTrue();
})->only();

test('an unbalanced draft journal entry cannot be posted', function () {
    // Arrange: Create a draft entry with UNBALANCED lines.
    $journalEntry = JournalEntry::factory()->create(['is_posted' => false]);
    $journalEntry->lines()->createMany([
        ['account_id' => Account::factory()->create()->id, 'debit' => 100.00],
        ['account_id' => Account::factory()->create()->id, 'credit' => 99.00], // Unbalanced!
    ]);

    // Assert: Expect the service's post method to reject this and throw an exception.
    expect(fn() => (new JournalEntryService())->post($journalEntry))
        ->toThrow(ValidationException::class);
})->only();

test('a draft journal entry can be freely modified before posting', function () {
    // Arrange: Create a draft journal entry.
    $journalEntry = JournalEntry::factory()->create([
        'is_posted' => false,
        'description' => 'Initial Draft Description'
    ]);

    $updateData = ['description' => 'Updated Draft Description'];

    // Act: Call the update method on the service.
    $wasUpdated = (new JournalEntryService())->update($journalEntry, $updateData);

    // Assert: Confirm the update was successful and the data was changed.
    expect($wasUpdated)->toBeTrue();
    expect($journalEntry->fresh()->description)->toBe('Updated Draft Description');
})->only();

test('a posted journal entry cannot be updated', function () {
    // Arrange: Create a journal entry that is already posted.
    $journalEntry = JournalEntry::factory()->create([
        'is_posted' => true,
        'description' => 'Original Posted Entry'
    ]);

    $updateData = ['description' => 'Attempted Unauthorized Update'];

    // Assert: Expect that calling the update method on the service throws our
    // specific exception, proving the action was blocked.
    expect(fn() => (new JournalEntryService())->update($journalEntry, $updateData))
        ->toThrow(UpdateNotAllowedException::class, 'Cannot modify a posted journal entry.');

    // Assert: As a final check, confirm that the data in the database did not change.
    $this->assertDatabaseHas('journal_entries', [
        'id' => $journalEntry->id,
        'description' => 'Original Posted Entry', // The description should be unchanged.
    ]);
})->only();

test('a posted journal entry cannot be deleted', function () {
    $journalEntry = JournalEntry::factory()->create(['is_posted' => true]);

    // Act: Attempt to delete the model.
    $deleteResult = $journalEntry->delete();

    // Assert: The observer should have returned false, cancelling the deletion.
    expect($deleteResult)->toBeFalse();

    // Assert: The model still exists in the database.
    $this->assertModelExists($journalEntry);
})->only();

test('posting a journal entry generates a cryptographic hash', function () {
    // Arrange: Create a draft journal entry with no hash.
    $journalEntry = JournalEntry::factory()->create([
        'is_posted' => false,
        'hash' => null
    ]);

    // Act: Post the entry using the service. This should trigger the observer.
    (new JournalEntryService())->post($journalEntry);

    // Assert: Check the model directly to confirm the hash was generated and saved.
    $journalEntry->refresh(); // Get the latest data from the database.

    expect($journalEntry->hash)->not->toBeNull();
    expect(strlen($journalEntry->hash))->toBe(64); // The length of a SHA-256 hash.
})->only();

test('posting a journal entry links to the previous entry hash to form an audit chain', function () {
    // Arrange: Create a company to scope the entries.
    $company = Company::factory()->create();

    // Arrange: Create the first entry, which is already posted and has a known hash.
    $firstEntry = JournalEntry::factory()->for($company)->create([
        'is_posted' => true,
        'entry_date' => now()->subDay(),
        'hash' => hash('sha256', 'first_entry_data'),
    ]);

    // Arrange: Create the second entry, which is still a draft.
    $secondEntry = JournalEntry::factory()->for($company)->create([
        'is_posted' => false,
        'entry_date' => now(),
    ]);

    // Act: Post the second entry using the service. This should trigger the observer logic.
    (new JournalEntryService())->post($secondEntry);

    // Assert: Check the second entry to confirm its 'previous_hash'
    // correctly links to the first entry's 'hash'.
    $secondEntry->refresh();
    expect($secondEntry->previous_hash)->toBe($firstEntry->hash);
})->only();
});
