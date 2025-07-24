<?php

use App\Events\InvoiceConfirmed;
use App\Exceptions\AccountIsDeprecatedException;
use App\Exceptions\PeriodIsLockedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\AnalyticAccount;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\BankStatementLine;
use App\Models\Company;
use App\Models\Account;
use App\Models\Currency;
use App\Models\DepreciationEntry;
use App\Models\Invoice;
use App\Models\InvoiceLine;
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
use App\Models\VendorBillLine;
use App\Services\AccountService;
use App\Services\AdjustmentDocumentService;
use App\Services\AssetService;
use App\Services\BankReconciliationService;
use App\Services\CompanyService;
use App\Services\CurrencyService;
use App\Services\InvoiceService;
use App\Services\JournalEntryService;
use App\Services\JournalService;
use App\Services\PaymentService;
use App\Services\VendorBillService;
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
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('a user is correctly related to their company for accounting contexts', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    // Verifies the structural integrity crucial for multi-company accounting.
    expect($user->company->id)->toBe($company->id);
});

test('duplicate tax ID for a company in the same fiscal country is prevented', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('creating a currency with an existing code is prevented', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('a partner record is soft-deleted to preserve historical transaction context', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    $partner = Partner::factory()->create();
    $partner->delete();

    // Partners, as non-financial records, should be soft-deleted to maintain auditability [2-5].
    $this->assertSoftDeleted($partner);
    expect(Partner::find($partner->id))->toBeNull(); // Verifies default query behavior
});

test('a soft-deleted partner can be retrieved using "withTrashed" for historical reporting', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    $partner = Partner::factory()->create();
    $partner->delete();

    // Ensures that historical data linked to soft-deleted entities is still accessible [2-5].
    expect(Partner::withTrashed()->find($partner->id))->not->toBeNull();
});

test('a product record is soft-deleted to preserve its history and linkages', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    $product = Product::factory()->create();
    $product->delete();

    // Products, like partners, are non-financial and subject to soft deletion principles [2-5].
    $this->assertSoftDeleted($product);
    expect(Product::find($product->id))->toBeNull();
});

test('a soft-deleted product can be retrieved with "withTrashed" for historical analysis', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    $product = Product::factory()->create();
    $product->delete();

    // Verifies the ability to access product history even after deactivation [2-5].
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
});

test('a product is correctly linked to its default income and expense general ledger accounts', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    $incomeAccount = Account::factory()->create(['type' => 'Income']);
    $expenseAccount = Account::factory()->create(['type' => 'Expense']);
    $product = Product::factory()->create([
        'income_account_id' => $incomeAccount->id,
        'expense_account_id' => $expenseAccount->id,
    ]);

    // Ensures proper accounting categorization for product sales and purchases [3, 5].
    expect($product->incomeAccount->id)->toBe($incomeAccount->id);
    expect($product->expenseAccount->id)->toBe($expenseAccount->id);
});

test('a tax is correctly linked to its designated general ledger tax account', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    $taxAccount = Account::factory()->create(['type' => 'Liability']); // e.g., VAT Payable
    $tax = Tax::factory()->create(['tax_account_id' => $taxAccount->id]);

    // Critical for accurate tax reporting and balance sheet presentation [3, 5].
    expect($tax->taxAccount->id)->toBe($taxAccount->id);
});

test('creating an account with a duplicate code for the same company is prevented', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('an account with existing transactions is marked as deprecated instead of being deleted', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('a deprecated account cannot be used for new financial transactions', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('creating a journal with an existing short code for the same company is prevented', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('a journal entry correctly calculates totals and assigns a user when created', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

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
});

test('creating an unbalanced journal entry is prevented', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('a balanced draft journal entry can be posted', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('an unbalanced draft journal entry cannot be posted', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create a draft entry with UNBALANCED lines.
    $journalEntry = JournalEntry::factory()->create(['is_posted' => false]);
    $journalEntry->lines()->createMany([
        ['account_id' => Account::factory()->create()->id, 'debit' => 100.00],
        ['account_id' => Account::factory()->create()->id, 'credit' => 99.00], // Unbalanced!
    ]);

    // Assert: Expect the service's post method to reject this and throw an exception.
    expect(fn() => (new JournalEntryService())->post($journalEntry))
        ->toThrow(ValidationException::class);
});

test('a draft journal entry can be freely modified before posting', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create a draft journal entry.
    $journalEntry = JournalEntry::factory()->create([
        'is_posted' => false,
        'description' => 'Initial Draft Description'
    ]);

    $updateData = ['description' => 'Updated Draft Description'];

    // Act: Call the update method on the service.
    $updatedEntry = (new JournalEntryService())->update($journalEntry, $updateData);

    // Assert: Confirm the update was successful and the data was changed.
    expect($updatedEntry)->toBeInstanceOf(JournalEntry::class);
    expect($updatedEntry->id)->toBe($journalEntry->id);
    expect($updatedEntry->description)->toBe('Updated Draft Description');
    expect($journalEntry->fresh()->description)->toBe('Updated Draft Description');
});

test('a posted journal entry cannot be updated', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('a posted journal entry cannot be deleted', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    $journalEntry = JournalEntry::factory()->create(['is_posted' => true]);

    // Act: Attempt to delete the model.
    $deleteResult = $journalEntry->delete();

    // Assert: The observer should have returned false, cancelling the deletion.
    expect($deleteResult)->toBeFalse();

    // Assert: The model still exists in the database.
    $this->assertModelExists($journalEntry);
});

test('posting a journal entry generates a cryptographic hash', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('posting a journal entry links to the previous entry hash to form an audit chain', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
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
});

test('posted journal entries accurately record the creating user and creation timestamp', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    $journalEntry = JournalEntry::factory()->create(['is_posted' => true, 'created_by_user_id' => $user->id]);

    // Vital for comprehensive audit logging and accountability [1, 9, 11, 12].
    expect($journalEntry->created_by_user_id)->toBe($user->id);
    expect($journalEntry->created_at)->toBeInstanceOf(Carbon::class);
});

test('the creation timestamp for a posted journal entry is immutable', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create a posted entry with a specific creation time.
    $initialCreatedAt = now()->subHour();
    $journalEntry = JournalEntry::factory()->create([
        'is_posted' => true,
        'created_at' => $initialCreatedAt,
        'updated_at' => $initialCreatedAt,
    ]);

    // Assert: Expect that any attempt to update the model through the service
    // will be blocked by the immutability rule.
    expect(fn() => (new JournalEntryService())->update($journalEntry, ['description' => 'New Desc']))
        ->toThrow(UpdateNotAllowedException::class);

    // Assert: Confirm the timestamp in the database has not changed.
    $freshEntry = $journalEntry->fresh();
    expect($freshEntry->created_at->timestamp)->toBe($initialCreatedAt->timestamp);
});

test('a journal entry correctly links its source type and ID to the originating document (e.g., invoice)', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    $invoice = Invoice::factory()->create(['status' => 'Posted']);
    $journalEntry = JournalEntry::factory()->for($invoice, 'source')->create([ // Using polymorphic factory
        'source_type' => 'App\\Models\\Invoice',
        'source_id' => $invoice->id,
    ]);

    // Ensures traceability and comprehensive audit trail for financial transactions [1, 9].
    expect($journalEntry->source_type)->toBe('App\\Models\\Invoice');
    expect($journalEntry->source_id)->toBe($invoice->id);
});

test('a draft customer invoice can be freely edited', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create a default income account to use for the lines.
    $incomeAccount = Account::factory()->create(['type' => 'Income']);
    $invoice = Invoice::factory()->create(['status' => Invoice::TYPE_DRAFT]);

    $updateData = [
        'lines' => [
            // Now we provide the required income_account_id for each line.
            ['description' => 'Service A', 'quantity' => 1, 'unit_price' => 100, 'income_account_id' => $incomeAccount->id],
            ['description' => 'Service B', 'quantity' => 1, 'unit_price' => 50, 'income_account_id' => $incomeAccount->id],
        ]
    ];

    $wasUpdated = (new InvoiceService())->update($invoice, $updateData);

    expect($wasUpdated)->toBeTrue();
    // Assuming your service doesn't calculate tax or subtotals in this test
    // you might need to adjust this assertion based on your service logic.
    // For now, let's just confirm the record was updated.
    $this->assertDatabaseCount('invoice_lines', 2);
});

test('a draft customer invoice can be freely deleted', function () {
   // Arrange: Create a user who will perform the action.
   $user = User::factory()->create();
   $this->actingAs($user);
   // Arrange: Create a draft invoice.
   $invoice = Invoice::factory()->create(['status' => Invoice::TYPE_DRAFT]);

   // Act: Call the delete method on the service.
   $wasDeleted = (new InvoiceService())->delete($invoice);

   // Assert: Confirm the deletion was successful.
   expect($wasDeleted)->toBeTrue();

   // Assert: Confirm the record is gone from the database.
   $this->assertModelMissing($invoice);
});

test('confirming an invoice assigns a sequential number, posts it, and creates a journal entry', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Fake events to ensure our action dispatches one.
    Event::fake();

    // Arrange: Create the company and user.
    $company = Company::factory()->create();
    $user = User::factory()->create();

    // Arrange: Create the default Accounts Receivable account the service will need.
    $arAccount = Account::factory()->for($company)->create(['type' => 'Receivable']);
    // Arrange: Set the configuration for this test run.
    config(['accounting.defaults.accounts_receivable_id' => $arAccount->id]);

    // ARRANGE: Create the default Sales Journal for invoices.
    $salesJournal = Journal::factory()->for($company)->create(['type' => 'Sale']);
    config(['accounting.defaults.sales_journal_id' => $salesJournal->id]); // <-- Add this


    // Arrange: Create a draft invoice that HAS lines and a real total.
    // This uses the 'has' factory relationship to create lines automatically.
    $invoice = Invoice::factory()->for($company)
        ->has(InvoiceLine::factory()->count(2), 'invoiceLines')
        ->create(['status' => Invoice::TYPE_DRAFT]);

    // The rest of your test (Act and Assert) remains the same.
    // Act: Call the confirm method on the service, passing the user for the audit trail.

    (new InvoiceService())->confirm($invoice, $user);

    // Assert: Check the invoice's state directly.
    $invoice->refresh();
    expect($invoice->status)->toBe(Invoice::TYPE_POSTED);
    expect($invoice->invoice_number)->not->toBeNull(); // It should now have a number.
    expect($invoice->journal_entry_id)->not->toBeNull(); // It should be linked to a JE.

    // Assert: Confirm the linked journal entry was actually created in the database.
    $this->assertDatabaseHas('journal_entries', ['id' => $invoice->journal_entry_id]);

    // Assert: Confirm that an event was dispatched for other parts of the system to listen to.
    Event::assertDispatched(InvoiceConfirmed::class);
});

test('a posted invoice cannot be directly modified', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create an invoice that is already in 'Posted' status.
    $invoice = Invoice::factory()->create([
        'status' => 'Posted',
        'total_amount' => 100.00
    ]);

    $updateData = ['total_amount' => 150.00];

    // Assert: Expect that calling the update method on the service
    // will throw our specific exception, proving the action was blocked.
    expect(fn() => (new InvoiceService())->update($invoice, $updateData))
        ->toThrow(UpdateNotAllowedException::class, 'Cannot modify a non-draft invoice.');

    // Assert: As a final check, confirm that the data in the database did not change.
    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'total_amount' => 10000, // The amount should be unchanged.
    ]);
});

test('a posted invoice cannot be deleted', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create an invoice that is already posted.
    $invoice = Invoice::factory()->create(['status' => 'Posted']);

    // Assert: Expect that calling the delete method on the service
    // will throw our specific exception, proving the action was blocked.
    expect(fn() => (new InvoiceService())->delete($invoice))
        ->toThrow(DeletionNotAllowedException::class, 'Cannot delete a posted invoice.');

    // Assert: As a final check, confirm the invoice still exists in the database.
    $this->assertModelExists($invoice);
});

test('resetting a posted invoice to draft is thoroughly logged and reverses the journal entry', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create a user who will perform this action.
    $user = User::factory()->create();

    // Arrange: Create a posted invoice that has a linked journal entry.
    $journalEntry = JournalEntry::factory()->create();
    $invoice = Invoice::factory()->create([
        'status' => Invoice::TYPE_POSTED,
        'journal_entry_id' => $journalEntry->id,
    ]);

    $reason = 'Correcting a data entry error before customer notification.';

    // Act: Call the service method to perform this sensitive action.
    (new InvoiceService())->resetToDraft($invoice, $user, $reason);

    // Assert: Check the invoice's state.
    $invoice->refresh();
    expect($invoice->status)->toBe(Invoice::TYPE_DRAFT);
    expect($invoice->journal_entry_id)->toBeNull(); // The link to the JE must be removed.

    // Assert: Check that the log was created correctly.
    expect($invoice->reset_to_draft_log)->toBeJson();
    $log = json_decode($invoice->reset_to_draft_log, true)[0]; // Get the first log entry
    expect($log['user_id'])->toBe($user->id);
    expect($log['reason'])->toBe($reason);
    expect(Carbon::parse($log['timestamp']))->toBeInstanceOf(Carbon::class);

    // Assert: Crucially, confirm the original journal entry was deleted.
    $this->assertModelMissing($journalEntry);
});

test('updating invoice lines correctly recalculates the invoice total amount and tax', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create accounts and products.
    $invoice = Invoice::factory()->create(['status' => Invoice::TYPE_DRAFT, 'total_amount' => 0, 'total_tax' => 0]);
    $tax = Tax::factory()->create(['rate' => 0.10]);
    $incomeAccount = Account::factory()->create(['type' => 'Income']); // Create the income account

    // Arrange: Prepare the new line data, including the required income_account_id.
    $updateData = [
        'lines' => [
            [
                'description' => 'Service Fee',
                'quantity' => 2,
                'unit_price' => 50,
                'tax_id' => $tax->id,
                'income_account_id' => $incomeAccount->id, // <-- Add this required ID
            ],
            [
                'description' => 'Consulting',
                'quantity' => 1,
                'unit_price' => 30,
                'tax_id' => $tax->id,
                'income_account_id' => $incomeAccount->id, // <-- And here
            ],
        ],
    ];

    // Act: Call the update method on the service.
    (new InvoiceService())->update($invoice, $updateData);

    // Assert
    $invoice->refresh();
    expect($invoice->total_tax)->toEqual('13.00');
    expect($invoice->total_amount)->toEqual('143.00');
});

test('posting an invoice correctly debits Accounts Receivable and credits Income/Tax', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create all the necessary models for the test.
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $arAccount = Account::factory()->for($company)->create(['type' => 'Receivable']);
    $incomeAccount = Account::factory()->for($company)->create(['type' => 'Income']);
    $taxAccount = Account::factory()->for($company)->create(['type' => 'Liability']);
    $salesJournal = Journal::factory()->for($company)->create(['type' => 'Sale']);

    // Arrange: Set the default accounts for this test run.
    // Your application needs to know which accounts to use automatically.
    config([
        'accounting.defaults.accounts_receivable_id' => $arAccount->id,
        'accounting.defaults.sales_journal_id' => $salesJournal->id,
    ]);

    // Arrange: Create a draft invoice with one line item.
    $invoice = Invoice::factory()->for($company)->create(['status' => Invoice::TYPE_DRAFT]);
    $tax = Tax::factory()->for($company)->create(['tax_account_id' => $taxAccount->id, 'rate' => 0.10]);
    $invoice->invoiceLines()->create([
        'description' => 'Item for Sale',
        'quantity' => 1,
        'unit_price' => 100,
        'tax_id' => $tax->id,
        'income_account_id' => $incomeAccount->id,
    ]);

    // Act: Call the confirm method directly on your service.
    (new InvoiceService())->confirm($invoice, $user);

    // Assert: Check that the main journal entry is balanced.
    $invoice->refresh(); // Get the latest data.
    $this->assertDatabaseHas('journal_entries', [
        'id' => $invoice->journal_entry_id,
        'total_debit' => 11000,
        'total_credit' => 11000,
        'is_posted' => true,
    ]);

    // Assert: Check each individual line of the journal entry.
    // 1. Assert the Debit to Accounts Receivable.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $invoice->journal_entry_id,
        'account_id' => $arAccount->id,
        'debit' => 11000,
    ]);
    // 2. Assert the Credit to the Income account.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $invoice->journal_entry_id,
        'account_id' => $incomeAccount->id,
        'credit' => 10000,
    ]);
    // 3. Assert the Credit to the Tax account.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $invoice->journal_entry_id,
        'account_id' => $taxAccount->id,
        'credit' => 1000,
    ]);
});

test('a draft vendor bill can be freely edited', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create a draft vendor bill.
    $company = Company::factory()->create();
    $vendorBill = VendorBill::factory()->create(['status' => VendorBill::TYPE_DRAFT]);
    $expenseAccount = Account::factory()->for($company)->create(['type' => 'Expense']);


    // Arrange: Prepare new line data. The service will calculate the new total.
    $updateData = [
        'lines' => [
            ['description' => 'Raw Materials', 'quantity' => 1, 'unit_price' => 150, 'expense_account_id' => $expenseAccount->id],
            ['description' => 'Shipping Cost', 'quantity' => 1, 'unit_price' => 100, 'expense_account_id' => $expenseAccount->id],
        ]
    ];

    // Act: Call the update method on the service.
    $wasUpdated = (new VendorBillService())->update($vendorBill, $updateData);

    // Assert: Confirm the update was successful and the total was recalculated.
    // We check for the integer value 25000 because of your MoneyCast (250.00 * 100).
    expect($wasUpdated)->toBeInstanceOf(App\Models\VendorBill::class);
    expect($vendorBill->fresh()->total_amount)->toEqual(250.0);
});

test('creating a vendor bill sets correct draft status, saves line items, and calculates totals', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Create necessary models
    $company = Company::factory()->create();
    $partner = Partner::factory()->for($company)->create();
    $currency = Currency::factory()->create();
    $expenseAccount = Account::factory()->for($company)->create(['type' => 'Expense']);
    $tax = Tax::factory()->for($company)->create(['rate' => 0.10]);

    // Arrange: Prepare data for creating a new vendor bill
    $createData = [
        'company_id' => $company->id,
        'vendor_id' => $partner->id,
        'currency_id' => $currency->id,
        'bill_reference' => 'BILL-001',
        'bill_date' => now()->toDateString(),
        'accounting_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'status' => VendorBill::TYPE_DRAFT, // This should be ignored as the service always creates drafts
        'notes' => 'Test vendor bill',
        'lines' => [
            [
                'description' => 'Office Supplies',
                'quantity' => 2,
                'unit_price' => 50.00,
                'tax_id' => $tax->id,
                'expense_account_id' => $expenseAccount->id,
            ],
            [
                'description' => 'Consulting Services',
                'quantity' => 1,
                'unit_price' => 100.00,
                'tax_id' => $tax->id,
                'expense_account_id' => $expenseAccount->id,
            ],
        ],
    ];

    // Act: Create the vendor bill using the service
    $vendorBillService = new VendorBillService();
    $vendorBill = $vendorBillService->create($createData);

    // Assert: Verify the vendor bill was created with correct draft status
    expect($vendorBill->status)->toBe(VendorBill::TYPE_DRAFT);

    // Assert: Verify line items were properly saved
    expect($vendorBill->lines)->toHaveCount(2);
    $firstLine = $vendorBill->lines->first();
    expect($firstLine->description)->toBe('Office Supplies');
    expect($firstLine->quantity)->toEqual(2.00);
    expect($firstLine->unit_price)->toEqual(50.00);
    expect($firstLine->tax_id)->toBe($tax->id);
    expect($firstLine->expense_account_id)->toBe($expenseAccount->id);

    // Assert: Verify totals are calculated correctly
    // Line 1: 2 * 50.00 = 100.00 subtotal, 100.00 * 0.10 = 10.00 tax
    // Line 2: 1 * 100.00 = 100.00 subtotal, 100.00 * 0.10 = 10.00 tax
    // Total: 200.00 subtotal, 20.00 tax, 220.00 total
    expect($vendorBill->total_amount)->toEqual(220.00);
    expect($vendorBill->total_tax)->toEqual(20.00);

    // Assert: Verify the accounting principles are followed
    // The create method should always create a draft
    expect($vendorBill->status)->toBe(VendorBill::TYPE_DRAFT);

    // Assert: Verify the vendor bill is properly linked to its components
    expect($vendorBill->company_id)->toBe($company->id);
    expect($vendorBill->vendor_id)->toBe($partner->id);
    expect($vendorBill->currency_id)->toBe($currency->id);
});

test('a draft vendor bill can be freely deleted', function () {
   // Arrange: Create a user who will perform the action.
   $user = User::factory()->create();
   $this->actingAs($user);
   // Arrange: Create a draft vendor bill.
   $vendorBill = VendorBill::factory()->create(['status' => VendorBill::TYPE_DRAFT]);

   // Act: Call the delete method on the service.
   $wasDeleted = (new VendorBillService())->delete($vendorBill);

   // Assert: Confirm the deletion was successful.
   expect($wasDeleted)->toBeTrue();
   $this->assertModelMissing($vendorBill);
});

test('confirming a vendor bill creates a linked journal entry', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Set up the company and user.
    $company = Company::factory()->create();
    $user = User::factory()->create();

    // Arrange: Set up the default accounts and journals the service will need.
    $apAccount = Account::factory()->for($company)->create(['type' => 'Payable']);
    $purchaseJournal = Journal::factory()->for($company)->create(['type' => 'Purchase']);
    $taxAccount = Account::factory()->for($company)->create(['type' => 'Asset']); // Tax on purchases is an asset

    config([
        'accounting.defaults.accounts_payable_id' => $apAccount->id,
        'accounting.defaults.purchase_journal_id' => $purchaseJournal->id,
        'accounting.defaults.tax_receivable_id' => $taxAccount->id, // <-- Add this line
    ]);

    // Arrange: Create a draft vendor bill with some lines so it has a value.
    $vendorBill = VendorBill::factory()->for($company)
        ->has(VendorBillLine::factory()->count(1), 'lines')
        ->create(['status' => VendorBill::TYPE_DRAFT]);

    // Act: Call the confirm method on the service.
    (new VendorBillService())->confirm($vendorBill, $user);

    // Assert: Check the state of the vendor bill directly.
    $vendorBill->refresh();
    expect($vendorBill->status)->toBe(VendorBill::TYPE_POSTED);
    expect($vendorBill->journal_entry_id)->not->toBeNull();

    // Assert: Confirm the linked journal entry was actually created.
    $this->assertDatabaseHas('journal_entries', ['id' => $vendorBill->journal_entry_id]);
});

test('a posted vendor bill cannot be modified', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create a vendor bill that is already posted.
    // The total_amount is 20000 because of your MoneyCast (200 * 100).
    $vendorBill = VendorBill::factory()->create(['status' => 'Posted', 'total_amount' => 20000]);

    // Arrange: Prepare the data for the unauthorized update attempt.
    $updateData = ['total_amount' => 25000];

    // Assert: Expect the service to block this action by throwing an exception.
    expect(fn() => (new VendorBillService())->update($vendorBill, $updateData))
        ->toThrow(UpdateNotAllowedException::class);

    // Assert: Double-check that the data in the database did not change.
    $this->assertDatabaseHas('vendor_bills', [
        'id' => $vendorBill->id,
        'total_amount' => 2000000, // The amount should be unchanged.
    ]);
});

test('a posted vendor bill cannot be deleted', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create a vendor bill that is already posted.
    $vendorBill = VendorBill::factory()->create(['status' => 'Posted']);

    // Assert: Expect that calling the delete method on the service
    // throws our specific exception, proving the action was blocked.
    expect(fn() => (new VendorBillService())->delete($vendorBill))
        ->toThrow(DeletionNotAllowedException::class, 'Cannot delete a posted vendor bill.');

    // Assert: Double-check that the model still exists in the database.
    $this->assertModelExists($vendorBill);
});

test('resetting a posted vendor bill to draft is logged and reverses the journal entry', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Create a posted vendor bill with a linked journal entry.
    $journalEntry = JournalEntry::factory()->create();
    $vendorBill = VendorBill::factory()->create([
        'status' => 'Posted',
        'journal_entry_id' => $journalEntry->id,
    ]);

    $reason = 'Supplier sent a revised bill with corrected quantities.';

    // Act: Call the service method to perform the action.
    (new VendorBillService())->resetToDraft($vendorBill, $user, $reason);

    // Assert: Check the state of the vendor bill.
    $vendorBill->refresh();
    expect($vendorBill->status)->toBe(VendorBill::TYPE_DRAFT);
    expect($vendorBill->journal_entry_id)->toBeNull();

    // Assert: Check that the log was created correctly.
    $log = $vendorBill->reset_to_draft_log[0]; // The cast already made it an array.

    expect($log['user_id'])->toBe($user->id);
    expect($log['reason'])->toBe($reason);
    expect(Carbon::parse($log['timestamp']))->toBeInstanceOf(Carbon::class);

    // Assert: Confirm the original journal entry was deleted to reverse the financial impact.
    $this->assertModelMissing($journalEntry);
});

test('posting a vendor bill correctly debits Expense/Asset and credits Accounts Payable', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Set up the company, user, and necessary accounts.
    $company = Company::factory()->create();
    $apAccount = Account::factory()->for($company)->create(['type' => 'Payable']);
    $expenseAccount = Account::factory()->for($company)->create(['type' => 'Expense']);
    $taxAccount = Account::factory()->for($company)->create(['type' => 'Asset']); // Tax on purchases
    $purchaseJournal = Journal::factory()->for($company)->create(['type' => 'Purchase']);

    // Arrange: Set the default accounts for the service to use.
    config([
        'accounting.defaults.accounts_payable_id' => $apAccount->id,
        'accounting.defaults.tax_receivable_id' => $taxAccount->id,
        'accounting.defaults.purchase_journal_id' => $purchaseJournal->id,
    ]);

    // Arrange: Create a draft vendor bill.
    $vendorBill = VendorBill::factory()->for($company)->create(['status' => VendorBill::TYPE_DRAFT]);
    $tax = Tax::factory()->for($company)->create(['rate' => 0.10]); // 10% tax

    // Arrange: Add a line item to the bill.
    $vendorBill->lines()->create([
        'description' => 'Office Supplies Purchase',
        'quantity' => 1,
        'unit_price' => 100.00, // This will be cast to 10000
        'tax_id' => $tax->id,
        'expense_account_id' => $expenseAccount->id,
    ]);

    // Act: Confirm the bill using the service.
    (new VendorBillService())->confirm($vendorBill, $user);

    // Assert: Check that the bill is now posted.
    $vendorBill->refresh();
    expect($vendorBill->status)->toBe(VendorBill::TYPE_POSTED);
    expect($vendorBill->journal_entry_id)->not->toBeNull();

    // Assert: Check that the journal entry lines are correct (using integer values).
    // The subtotal is 100.00 * 1 = 100.00 (stored as 10000)
    // The tax is 100.00 * 10% = 10.00 (stored as 1000)
    // The total is 110.00 (stored as 11000)
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $vendorBill->journal_entry_id,
        'account_id' => $expenseAccount->id,
        'debit' => 10000, // Debit Expense for the subtotal
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $vendorBill->journal_entry_id,
        'account_id' => $taxAccount->id,
        'debit' => 1000, // Debit Tax Asset for the tax amount
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $vendorBill->journal_entry_id,
        'account_id' => $apAccount->id,
        'credit' => 11000, // Credit Accounts Payable for the total amount
    ]);
});

test('confirming an inbound payment creates a linked journal entry', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Set up the company, customer, user, and necessary accounts.
    $company = Company::factory()->create();
    $customer = Partner::factory()->for($company)->create(['type' => 'Customer']);
    $bankAccount = Account::factory()->for($company)->create(['type' => 'Bank']);
    $arAccount = Account::factory()->for($company)->create(['type' => 'Receivable']);
    $currency = Currency::factory()->create(['code' => 'USD']);

    // Arrange: Create a specific journal for bank transactions.
    $bankJournal = Journal::factory()->for($company)->create(['type' => 'Bank']);

    // Arrange: Set up the default accounts the service will need.
    config([
        'accounting.defaults.default_bank_account_id' => $bankAccount->id,
        'accounting.defaults.accounts_receivable_id' => $arAccount->id,
    ]);

    // Arrange: Prepare the COMPLETE data for an inbound payment.
    $paymentData = [
        'company_id' => $company->id,
        'journal_id' => $bankJournal->id,              // <-- 1. ADD this required ID
        'currency_id' => $currency->id,
        'paid_to_from_partner_id' => $customer->id,     // <-- 2. FIX the key name
        'payment_date' => now()->toDateString(),      // <-- 3. ADD the payment date
        'payment_type' => Payment::TYPE_INBOUND,
        'amount' => 500.00, // 500.00 in integer form
    ];

    // Act: Create and confirm the payment using the service.
    $paymentService = new PaymentService();
    $payment = $paymentService->create($paymentData, $user);
    $payment = $paymentService->confirm($payment, $user);
    // Assert: Check that the payment is confirmed and linked to a journal entry.
    expect($payment->status)->toBe(Payment::STATUS_CONFIRMED);
    expect($payment->journal_entry_id)->not->toBeNull();

    // Assert: Confirm the journal entry exists and is correct.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $payment->journal_entry_id,
        'account_id' => $bankAccount->id,
        'debit' => 50000, // Dr Bank
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $payment->journal_entry_id,
        'account_id' => $arAccount->id,
        'credit' => 50000, // Cr Accounts Receivable
    ]);
});

test('a confirmed payment is immutable', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    $company = Company::factory()->create();
    $currency = Currency::factory()->create(['code' => 'USD']);

    // --- THIS IS THE FIX ---
    // Arrange: Set up the default accounts the service needs to create the payment.
    $bankAccount = Account::factory()->for($company)->create(['type' => 'Bank']);
    $arAccount = Account::factory()->for($company)->create(['type' => 'Receivable']);
    config([
        'accounting.defaults.default_bank_account_id' => $bankAccount->id,
        'accounting.defaults.accounts_receivable_id' => $arAccount->id,
    ]);
    // --- END FIX ---

    // Arrange: Prepare the payment data.
    $paymentData = [
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'journal_id' => Journal::factory()->for($company)->create()->id,
        'paid_to_from_partner_id' => Partner::factory()->for($company)->create()->id,
        'payment_date' => now()->toDateString(),
        'payment_type' => Payment::TYPE_INBOUND,
        'amount' => 100.00, // 100.00
    ];
    $paymentService = new PaymentService();
    $payment = $paymentService->create($paymentData, $user);
    $payment = $paymentService->confirm($payment, $user);
    
    // Assert: Expect that any attempt to update the payment will be blocked.
    expect(fn() => (new PaymentService())->update($payment, ['amount' => 20000]))
        ->toThrow(UpdateNotAllowedException::class);

    // Assert: Double-check that the amount in the database did not change.
    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'amount' => 10000,
    ]);
});

test('an incoming payment correctly debits Bank and credits Accounts Receivable', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Set up the company, customer, and user.
    $company = Company::factory()->create();
    $customer = Partner::factory()->for($company)->create(['type' => 'Customer']);
    $currency = Currency::factory()->create(['code' => 'USD']);

    // Arrange: Set up the default accounts the service will need.
    $bankAccount = Account::factory()->for($company)->create(['type' => 'Bank']);
    $arAccount = Account::factory()->for($company)->create(['type' => 'Receivable']);
    config([
        'accounting.defaults.default_bank_account_id' => $bankAccount->id,
        'accounting.defaults.accounts_receivable_id' => $arAccount->id,
    ]);

    // Arrange: Prepare the data for an inbound payment.
    $paymentData = [
        'company_id' => $company->id,
        'journal_id' => Journal::factory()->for($company)->create()->id,
        'currency_id' => $currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'payment_date' => now()->toDateString(),
        'payment_type' => Payment::TYPE_INBOUND,
        'amount' => 100.00, // Use a float, the MoneyCast will handle it.
    ];

    // Act: Create and confirm the payment using the service.
    $paymentService = new PaymentService();
    $payment = $paymentService->create($paymentData, $user);
    $payment = $paymentService->confirm($payment, $user);
    
    // Assert: Check that the payment is confirmed and linked to a journal entry.
    expect($payment->status)->toBe(Payment::STATUS_CONFIRMED);
    expect($payment->journal_entry_id)->not->toBeNull();

    // Assert: Confirm the journal entry lines are correct in the database.
    // We check for the integer value 10000 (100.00 * 100).
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $payment->journal_entry_id,
        'account_id' => $bankAccount->id,
        'debit' => 10000, // Dr Bank
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $payment->journal_entry_id,
        'account_id' => $arAccount->id,
        'credit' => 10000, // Cr Accounts Receivable
    ]);
});

test('an outgoing payment correctly debits Accounts Payable and credits Bank', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Set up the company, a vendor, and a user.
    $currency = Currency::factory()->create(['code' => 'IQD']); // Create ONE currency
    $company = Company::factory()->create(['currency_id' => $currency->id]);
    $vendor = Partner::factory()->for($company)->create();

    // Arrange: Set up the default accounts the service will need.
    $bankAccount = Account::factory()->for($company)->create(['type' => 'Bank']);
    $apAccount = Account::factory()->for($company)->create(['type' => 'Payable']);

    // Arrange: Create the journal and tell it which currency to use.
    $bankJournal = Journal::factory()->for($company)->create([
        'type' => 'Bank',
        'currency_id' => $currency->id,
    ]);

    config([
        'accounting.defaults.default_bank_account_id' => $bankAccount->id,
        'accounting.defaults.accounts_payable_id' => $apAccount->id,
    ]);

    // Arrange: Prepare the data for an outbound payment.
    $paymentData = [
        'company_id' => $company->id,
        'journal_id' => $bankJournal->id,
        'paid_to_from_partner_id' => $vendor->id,
        'payment_date' => now()->toDateString(),
        'currency_id' => $currency->id, // Use the currency we created
        'payment_type' => 'Outbound',
        'amount' => 250.00,
    ];

    // Act: Create and confirm the payment using the service.
    $paymentService = new PaymentService();
    $payment = $paymentService->create($paymentData, $user);
    $payment = $paymentService->confirm($payment, $user);
    
    // Assert: Check that the payment is confirmed and linked to a journal entry.
    expect($payment->status)->toBe(Payment::STATUS_CONFIRMED);
    expect($payment->journal_entry_id)->not->toBeNull();

    // Assert: Confirm the journal entry lines are correct in the database.
    // We check for the integer value 25000 (250.00 * 100).
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $payment->journal_entry_id,
        'account_id' => $apAccount->id,
        'debit' => 25000, // Dr Accounts Payable
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $payment->journal_entry_id,
        'account_id' => $bankAccount->id,
        'credit' => 25000, // Cr Bank
    ]);
});

test('a posted credit note is immutable', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Create a posted credit note with a specific amount.
    // We use an integer because of your MoneyCast (50.00 * 100 = 5000).
    $creditNote = AdjustmentDocument::factory()->create([
        'status' => 'Posted',
        'total_amount' => 50.00,
    ]);

    // Assert: Expect that any attempt to update the posted credit note will be blocked
    // by the service and throw a specific exception.
    expect(fn() => (new AdjustmentDocumentService())->update($creditNote, ['total_amount' => 9999]))
        ->toThrow(UpdateNotAllowedException::class);

    // Assert: As a final check, confirm the amount in the database did not change.
    $this->assertDatabaseHas('adjustment_documents', [
        'id' => $creditNote->id,
        'total_amount' => 5000,
    ]);
});

test('a credit note explicitly references the original invoice it corrects for clear auditability', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    $originalInvoice = Invoice::factory()->create(['status' => 'Posted']);
    $creditNote = AdjustmentDocument::factory()->create(['original_invoice_id' => $originalInvoice->id]);

    // Essential for a clear and traceable audit trail [1, 2, 4, 9].
    expect($creditNote->original_invoice_id)->toBe($originalInvoice->id);
    expect($creditNote->originalInvoice->id)->toBe($originalInvoice->id);
});

test('posting a credit note generates the correct reverse journal entry', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Set up the company, user, and necessary accounts.
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $arAccount = Account::factory()->for($company)->create(['type' => 'Receivable']);
    $incomeAccount = Account::factory()->for($company)->create(['type' => 'Income']);
    $taxAccount = Account::factory()->for($company)->create(['type' => 'Liability']);
    // Arrange: Create a specific journal for sales.
    $salesJournal = Journal::factory()->for($company)->create(['type' => 'Sale']);


    // Arrange: Create the original posted invoice.
    $originalInvoice = Invoice::factory()->for($company)->create(['status' => 'Posted']);

    // Arrange: Create a draft credit note to reverse the invoice.
    // Note: We use integers for money values (110.00 * 100 = 11000).
    $creditNote = AdjustmentDocument::factory()->for($company)->create([
        'status' => 'Draft',
        'original_invoice_id' => $originalInvoice->id,
        'total_amount' => 110.00,
        'total_tax' => 10.00,
        // The credit note should also have lines detailing what is being credited.
        // For simplicity, we'll assume the service can derive the accounts.
    ]);

    // Arrange: Set up default accounts for the service to use.
    config([
        'accounting.defaults.accounts_receivable_id' => $arAccount->id,
        // In a real system, the service would get the income/tax accounts from the credit note lines.
        'accounting.defaults.default_income_account_id' => $incomeAccount->id,
        'accounting.defaults.default_tax_account_id' => $taxAccount->id,
        'accounting.defaults.sales_journal_id' => $salesJournal->id,
    ]);

    // Act: Post the credit note using the service.
    (new AdjustmentDocumentService())->post($creditNote, $user);

    // Assert: Check the credit note's state.
    $creditNote->refresh();
    expect($creditNote->status)->toBe('Posted');
    expect($creditNote->journal_entry_id)->not->toBeNull();

    // Assert: Confirm the REVERSE journal entry lines were created correctly.
    // We check for integer values.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $creditNote->journal_entry_id,
        'account_id' => $incomeAccount->id,
        'debit' => 10000, // Dr Income (total_amount - total_tax)
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $creditNote->journal_entry_id,
        'account_id' => $taxAccount->id,
        'debit' => 1000, // Dr Tax Payable
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $creditNote->journal_entry_id,
        'account_id' => $arAccount->id,
        'credit' => 11000, // Cr Accounts Receivable
    ]);
});

test('a financial transaction cannot be created in a locked period', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create a company and lock its books up to a month ago.
    $company = Company::factory()->create();
    LockDate::factory()->for($company)->create([
        'locked_until' => now()->subMonth(),
    ]);

    // Arrange: Prepare data for a journal entry with a date inside the locked period.
    $lockedPeriodData = [
        'company_id' => $company->id,
        'entry_date' => now()->subMonths(2)->toDateString(), // This date is locked.
        'reference' => 'LOCKED-PERIOD-ENTRY',
        'lines' => [
            ['account_id' => Account::factory()->for($company)->create()->id, 'debit' => 10000],
            ['account_id' => Account::factory()->for($company)->create()->id, 'credit' => 10000],
        ],
    ];

    // Assert: Expect the service to throw our specific exception when it detects the locked date.
    expect(fn() => (new JournalEntryService())->create($lockedPeriodData))
        ->toThrow(PeriodIsLockedException::class);

    // Assert: As a final check, confirm that no journal entry was created.
    $this->assertDatabaseMissing('journal_entries', [
        'reference' => 'LOCKED-PERIOD-ENTRY'
    ]);
});

test('a financial transaction in a locked period cannot be modified', function () {

    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Create a company and lock its books up to a month ago.
    $company = Company::factory()->create();
    LockDate::factory()->for($company)->create([
        'locked_until' => now()->subMonth(),
    ]);

    // Arrange: Create a draft journal entry with a date inside the locked period.
    $journalEntry = JournalEntry::factory()->for($company)->create([
        'entry_date' => now()->subMonths(2)->toDateString(),
        'description' => 'Original Description',
    ]);

    // Assert: Expect the service to throw our specific exception when it detects
    // that the entry being modified is in a locked period.
    expect(fn() => (new JournalEntryService())->update($journalEntry, ['description' => 'New Description']))
        ->toThrow(PeriodIsLockedException::class);

    // Assert: As a final check, confirm that the description was not changed in the database.
    $this->assertDatabaseHas('journal_entries', [
        'id' => $journalEntry->id,
        'description' => 'Original Description',
    ]);
});

test('creating a financial record is logged in the audit trail', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();

    // Arrange: We need to simulate this user being logged in so the observer
    // can identify who is performing the action.
    $this->actingAs($user);

    // Arrange: Prepare the data for the journal entry.
    $company = Company::factory()->create();
    $entryData = [
        'company_id' => $company->id,
        'journal_id' => Journal::factory()->for($company)->create()->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'LOGGED-JE-001',
        'lines' => [
            ['account_id' => Account::factory()->for($company)->create()->id, 'debit' => 5000],
            ['account_id' => Account::factory()->for($company)->create()->id, 'credit' => 5000],
        ],
        'created_by_user_id' => $user->id,
    ];

    // Act: Create the entry using the service. This action will trigger the observer.
    $journalEntry = (new JournalEntryService())->create($entryData);

    // Assert: Check the database to confirm that the observer created the audit log entry.
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'event_type' => 'record_created',
        'auditable_type' => JournalEntry::class, // Use the class name for clarity
        'auditable_id' => $journalEntry->id,
    ]);
});

test('a status change from draft to posted is logged in the audit trail', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();

    // Arrange: Simulate this user being logged in so the observer can find them.
    $this->actingAs($user);

    // Arrange: Create a draft invoice.
    $invoice = Invoice::factory()
        ->has(InvoiceLine::factory()->count(1), 'invoiceLines')
        ->create(['status' => Invoice::TYPE_DRAFT]);

    // Arrange: Set up the default accounts/journals needed for the confirm() method.
    config([
        'accounting.defaults.accounts_receivable_id' => Account::factory()->create()->id,
        'accounting.defaults.sales_journal_id' => Journal::factory()->create()->id,
    ]);

    // Act: Confirm the invoice using the service. This should trigger the AuditLogObserver.
    (new InvoiceService())->confirm($invoice, $user);

    // Assert: Find the audit log entry that was just created for this invoice update.
    $log = AuditLog::where('auditable_type', Invoice::class)
        ->where('auditable_id', $invoice->id)
        ->latest('id')->first();

    // Assert that the log entry exists and contains the correct information.
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($user->id);
    expect($log->event_type)->toBe('status_changed');

    // Check that the 'old_values' correctly recorded the original status.
    expect($log->old_values['status'])->toBe(Invoice::TYPE_DRAFT);
    // Check that the 'new_values' correctly recorded the new status.
    expect($log->new_values['status'])->toBe(Invoice::TYPE_POSTED);
});

test('resetting an invoice to draft is logged as a status change in audit logs', function () {
    // Arrange: Create a user and log them in for the test.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Create a posted invoice. It needs a journal entry to be realistic.
    $invoice = Invoice::factory()->create([
        'status' => Invoice::TYPE_POSTED,
        'journal_entry_id' => \App\Models\JournalEntry::factory()->create()->id,
    ]);
    $reason = 'Correcting an error.';

    // Act: Call the service method. This will trigger the AuditLogObserver.
    (new InvoiceService())->resetToDraft($invoice, $user, $reason);

    // Assert: Find the audit log that was created for this action.
    $log = AuditLog::where('auditable_type', Invoice::class)
        ->where('auditable_id', $invoice->id)
        ->latest('id')->first();

    // Assert: Check that the log has the correct information.
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($user->id);
    expect($log->event_type)->toBe('status_changed');

    // Assert: Check the old and new status values in the log.
    expect($log->old_values['status'])->toBe(Invoice::TYPE_POSTED);
    expect($log->new_values['status'])->toBe(Invoice::TYPE_DRAFT);
});

test('a journal entry line can be assigned to an analytic account', function () {
    // Arrange: Create a user and log them in for the test.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Create the analytic account we want to assign.
    $analyticAccount = AnalyticAccount::factory()->create();

    // Arrange: Prepare data for a journal entry, including the analytic_account_id on a line.
    $entryData = [
        'company_id' => Company::factory()->create()->id,
        'journal_id' => Journal::factory()->create()->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'ANALYTIC-TEST-001',
        'lines' => [
            [
                'account_id' => Account::factory()->create()->id,
                'debit' => 10000,
                'description' => 'Expense for Project Alpha',
                'analytic_account_id' => $analyticAccount->id, // Assign the analytic account
            ],
            [
                'account_id' => Account::factory()->create()->id,
                'credit' => 10000,
            ],
        ],
    ];

    // Act: Create the journal entry using the service.
    $journalEntry = (new JournalEntryService())->create($entryData);

    // Assert: Check the database to confirm the line was created with the correct analytic account.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'analytic_account_id' => $analyticAccount->id,
    ]);
});

test('running depreciation generates correct depreciation and journal entries', function () {
    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);
    // Arrange: Create the main asset account as well.
    $assetAccount = Account::factory()->create(['type' => 'Asset']);

    // Arrange: Set up the company, user, and necessary accounts.
    $company = Company::factory()->create();
    $expenseAccount = Account::factory()->for($company)->create(['type' => 'Expense']);
    $accumulatedDepAccount = Account::factory()->for($company)->create(['type' => 'Asset']); // Contra-asset

    // Arrange: Set up the default accounts and journal the service will need.
    config([
        'accounting.defaults.depreciation_expense_account_id' => $expenseAccount->id,
        'accounting.defaults.accumulated_depreciation_account_id' => $accumulatedDepAccount->id,
        'accounting.defaults.depreciation_journal_id' => Journal::factory()->for($company)->create()->id,
    ]);

    // Arrange: Create an asset to be depreciated.
    $asset = Asset::factory()->for($company)->create([
        'purchase_value' => 1200.00, // 1,200.00
        'useful_life_years' => 1,
        'asset_account_id' => $assetAccount->id,
        'depreciation_expense_account_id' => $expenseAccount->id,
        'accumulated_depreciation_account_id' => $accumulatedDepAccount->id,
    ]);

    // Act: Run the depreciation for this asset using the service.
    (new AssetService())->runDepreciation($asset, $user);

    // Assert: Check that a depreciation entry was created for the correct amount.
    // The amount should be 10000 (1200.00 / 12 months = 100.00 per month).
    $this->assertDatabaseHas('depreciation_entries', [
        'asset_id' => $asset->id,
        'amount' => 10000,
        'status' => 'Posted',
    ]);

    // Assert: Check that the depreciation entry is linked to a journal entry.
    $depreciationEntry = DepreciationEntry::where('asset_id', $asset->id)->first();
    expect($depreciationEntry->journal_entry_id)->not->toBeNull();

    // Assert: Check that the journal entry lines are correct.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $depreciationEntry->journal_entry_id,
        'account_id' => $expenseAccount->id,
        'debit' => 10000, // Dr Depreciation Expense
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $depreciationEntry->journal_entry_id,
        'account_id' => $accumulatedDepAccount->id,
        'credit' => 10000, // Cr Accumulated Depreciation
    ]);
});

test('a journal entry cannot be created with a non-existent account ID', function () {

    // Arrange: Create a user who will perform the action.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Prepare data for a journal entry where one line uses an invalid account ID.
    $company = Company::factory()->create();
    $nonExistentAccountId = 99999;

    $entryData = [
        'company_id' => $company->id,
        'journal_id' => \App\Models\Journal::factory()->create()->id,
        'entry_date' => now()->toDateString(),
        'reference' => 'INVALID-ACCOUNT-REF',
        'lines' => [
            // This line uses an account ID that does not exist.
            ['account_id' => $nonExistentAccountId, 'debit' => 10000],
            // This line is valid.
            ['account_id' => Account::factory()->for($company)->create()->id, 'credit' => 10000],
        ],
    ];

    // Assert: Expect the service to throw a ValidationException because the 'exists' rule fails.
    expect(fn() => (new JournalEntryService())->create($entryData))
        ->toThrow(ValidationException::class);
});

test('modifications after a reset-to-draft are fully audited upon re-posting', function () {
    // Arrange: Set up the user and log them in.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Create the initial posted invoice with a known total.
    $invoice = Invoice::factory()->create([
        'status' => Invoice::TYPE_POSTED,
        'total_amount' => 10000, // 100.00
        'journal_entry_id' => \App\Models\JournalEntry::factory()->create()->id,
    ]);

    // Arrange: Set up necessary config for services.
    config([
        'accounting.defaults.accounts_receivable_id' => Account::factory()->create()->id,
        'accounting.defaults.sales_journal_id' => Journal::factory()->create()->id,
    ]);

    $invoiceService = new InvoiceService();
    $reason = 'Initial error correction';

    // Act 1: Reset the posted invoice to draft.
    $invoiceService->resetToDraft($invoice, $user, $reason);

    // Act 2: Modify the invoice while it is in the draft state.
    // We will update the lines, which should change the total amount.
    $invoiceService->update($invoice, [
        'lines' => [
            ['description' => 'New Service', 'quantity' => 1, 'unit_price' => 150.00, 'income_account_id' => Account::factory()->create()->id],
        ]
    ]); // The total_amount should now be 15000 (150.00).

    // Act 3: Re-post (confirm) the modified invoice.
    $invoiceService->confirm($invoice, $user);

    // Assert: Find the latest audit log for this invoice. It should be from the 'confirm' action.
    // Assert: Find the last two audit logs for this invoice.
    $logs = AuditLog::where('auditable_type', Invoice::class)
        ->where('auditable_id', $invoice->id)
        ->latest('id')->take(2)->get();

    // The first log in the collection (the most recent one) is from the 'confirm' action.
    $confirmLog = $logs[0];
    expect($confirmLog->event_type)->toBe('status_changed');
    expect($confirmLog->old_values['status'])->toBe(Invoice::TYPE_DRAFT);
    expect($confirmLog->new_values['status'])->toBe(Invoice::TYPE_POSTED);
    // Assert that 'total_amount' was NOT part of this specific change.
    expect($confirmLog->new_values)->not->toHaveKey('total_amount');

    // The second log in the collection is from the 'update' action.
    $updateLog = $logs[1];
    expect($updateLog->event_type)->toBe('record_updated');
    // Assert that this log correctly captured the change in the total amount.
    expect($updateLog->old_values['total_amount'])->toBe(10000);
    expect($updateLog->new_values['total_amount'])->toBe(15000);
});

test('bank reconciliation moves funds from outstanding to bank and updates payment status', function () {
    // Arrange: Set up the user, company, and necessary accounts.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Create company WITH a currency
    $company = Company::factory()->create();
    $currency = Currency::factory()->create(['code' => 'IQD']);
    $company->update(['currency_id' => $currency->id]);

    $bankAccount = Account::factory()->for($company)->create(['type' => 'Bank']);
    $outstandingReceiptsAccount = Account::factory()->for($company)->create(['type' => 'Asset']);

    // --- FIX STARTS HERE ---
    // 1. Create the account for the credit line explicitly for the same company.
    $otherAssetAccount = Account::factory()->for($company)->create(['type' => 'Asset']);
    // --- FIX ENDS HERE ---


    // Arrange: Set up the default accounts for the two-step process.
    config([
        'accounting.defaults.default_bank_account_id' => $bankAccount->id,
        'accounting.defaults.outstanding_receipts_account_id' => $outstandingReceiptsAccount->id,
    ]);

    // Arrange: Create a "Confirmed" payment with the specific currency.
    $payment = Payment::factory()->for($company)->create([
        'status' => 'Confirmed',
        'amount' => 150.00, // 150.00
        'currency_id' => $currency->id,
    ]);

    // Arrange: Simulate the initial journal entry, ensuring it also has the currency.
    $initialJE = JournalEntry::factory()->for($company)->create([
        'currency_id' => $currency->id,
    ]);

    // --- FIX STARTS HERE ---
    // 2. Use the explicitly created account ID.
    $initialJE->lines()->createMany([
        ['account_id' => $outstandingReceiptsAccount->id, 'debit' => 15000],
        ['account_id' => $otherAssetAccount->id, 'credit' => 15000],
    ]);
    // --- FIX ENDS HERE ---

    $payment->update(['journal_entry_id' => $initialJE->id]);

    // Arrange: Simulate a line from an imported bank statement that matches the payment.
    $statementLine = BankStatementLine::factory()->create(['amount' => 15000]);

    // Act: Reconcile the payment against the bank statement line using a new service.
    (new BankReconciliationService())->reconcilePayment($payment, $statementLine, $user);

    // Assert 1: The payment's status is now 'Reconciled'.
    expect($payment->fresh()->status)->toBe('Reconciled');

    // Assert 2: A *new* journal entry was created for the reconciliation.
    $reconciliationJE = JournalEntry::latest('id')->first();
    expect($reconciliationJE->id)->not->toBe($initialJE->id);

    // Assert 3: The new journal entry has the correct lines.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $reconciliationJE->id,
        'account_id' => $bankAccount->id,
        'debit' => 15000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $reconciliationJE->id,
        'account_id' => $outstandingReceiptsAccount->id,
        'credit' => 15000,
    ]);
});

test('posting a foreign currency invoice creates a journal entry with correct base and original amounts', function () {
    // Arrange: Create a user and log them in.
    $user = User::factory()->create();
    $this->actingAs($user);

    // Arrange: Set up company and currencies.
    // Base currency is IQD. Foreign currency is USD.
    $company = Company::factory()->create();
    $baseCurrency = Currency::factory()->create(['code' => 'IQD', 'exchange_rate' => 1.0]);
    $foreignCurrency = Currency::factory()->create(['code' => 'USD', 'exchange_rate' => 1450.0]);

    // The company's main currency is IQD.
    $company->update(['currency_id' => $baseCurrency->id]);

    // Arrange: Set up necessary accounts and journal.
    $arAccount = Account::factory()->for($company)->create(['type' => 'Receivable']);
    $incomeAccount = Account::factory()->for($company)->create(['type' => 'Income']);
    $salesJournal = Journal::factory()->for($company)->create(['type' => 'Sale']);
    config([
        'accounting.defaults.accounts_receivable_id' => $arAccount->id,
        'accounting.defaults.sales_journal_id' => $salesJournal->id,
    ]);

    // Arrange: Create a draft invoice for $100 USD.
    $invoice = Invoice::factory()->for($company)->create([
        'status' => Invoice::TYPE_DRAFT,
        'currency_id' => $foreignCurrency->id, // This invoice is in USD.
    ]);
    $invoice->invoiceLines()->create([
        'description' => 'Service in USD',
        'quantity' => 1,
        'unit_price' => 100.00, // $100.00
        'income_account_id' => $incomeAccount->id,
    ]);

    // Act: Confirm the invoice. This should trigger currency conversion.
    (new InvoiceService())->confirm($invoice, $user);

    // Assert: Check the journal entry. Amounts should be in the base currency (IQD).
    // $100 USD * 1450 = 145,000 IQD.
    $invoice->refresh();
    $this->assertDatabaseHas('journal_entries', [
        'id' => $invoice->journal_entry_id,
        'total_debit' => 14500000, // 145,000.00 in integers
        'total_credit' => 14500000,
    ]);

    // Assert: Check the journal entry lines for multi-currency details.
    // 1. Assert the Debit to Accounts Receivable.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $invoice->journal_entry_id,
        'account_id' => $arAccount->id,
        'debit' => 14500000, // 145,000.00 IQD
        'original_currency_amount' => 10000, // $100.00 USD
        'exchange_rate_at_transaction' => 1450.0,
        'currency_id' => $foreignCurrency->id,
    ]);

    // 2. Assert the Credit to the Income account.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $invoice->journal_entry_id,
        'account_id' => $incomeAccount->id,
        'credit' => 14500000, // 145,000.00 IQD
        'original_currency_amount' => 10000, // $100.00 USD
        'exchange_rate_at_transaction' => 1450.0,
        'currency_id' => $foreignCurrency->id,
    ]);
});
