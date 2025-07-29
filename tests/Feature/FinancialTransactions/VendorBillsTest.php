<?php

use App\Events\VendorBillConfirmed;
use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\PeriodIsLockedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\LockDate;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->company = $this->createConfiguredCompany();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
});

test('a draft vendor bill can be confirmed, which posts it and dispatches an event', function () {
    // Arrange: Ensure events are being listened for.
    Event::fake();

    // Arrange: Create a draft vendor bill.
    $currencyCode = $this->company->currency->code;
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'status' => 'draft',
        'total_amount' => Money::of(0, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);

    // Act: Call the confirm method on the service.
    (app(VendorBillService::class))->confirm($vendorBill, $this->user);

    // Assert: The vendor bill's status is now 'posted'.
    $this->assertDatabaseHas('vendor_bills', [
        'id' => $vendorBill->id,
        'status' => 'posted',
    ]);

    // Assert: An event was dispatched.
    Event::assertDispatched(VendorBillConfirmed::class, function ($event) use ($vendorBill) {
        return $event->vendorBill->id === $vendorBill->id;
    });
});

test('confirming a vendor bill generates the correct journal entry', function () {
    // Arrange: Set up the necessary accounts and a purchases journal.
    $payableAccount = Account::factory()->for($this->company)->create(['type' => 'Payable']);
    $taxAccount = Account::factory()->for($this->company)->create(['type' => 'Other Current Liability']);
    $expenseAccount = Account::factory()->for($this->company)->create(['type' => 'Expense']);
    $purchasesJournal = Journal::factory()->for($this->company)->create(['type' => 'Purchase']);
    $this->company->update([
        'default_accounts_payable_id' => $payableAccount->id,
        'default_tax_receivable_id' => $taxAccount->id,
        'default_purchase_journal_id' => $purchasesJournal->id,
    ]);

    $this->company->refresh();

    // Arrange: Create a product linked to the expense account.
    $currencyCode = $this->company->currency->code;
    $product = Product::factory()->for($this->company)->create([
        'expense_account_id' => $expenseAccount->id,
        'unit_price' => Money::of(50, $currencyCode),
    ]);

    // Arrange: Create a draft vendor bill with one line item.
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'status' => 'draft',
    ]);

    // Create the line without manually setting subtotal or tax. Let the observer handle it.
    $vendorBill->lines()->create([
        'product_id' => $product->id,
        'description' => 'Test Product',
        'quantity' => 3,
        'unit_price' => Money::of(50, $currencyCode),
        'expense_account_id' => $expenseAccount->id,
    ]);

    // **THE FIX:** Refresh the model instance from the database to get the updated totals.
    $vendorBill->refresh();

    // Act: Confirm the vendor bill.
    (app(VendorBillService::class))->confirm($vendorBill, $this->user);

    // Assert: A single journal entry was created.
    $this->assertDatabaseCount('journal_entries', 1);

    // Assert: The journal entry has the correct details.
    $journalEntry = JournalEntry::first();
    expect($journalEntry->journal_id)->toBe($purchasesJournal->id);
    $expectedTotal = Money::of(150, $currencyCode);
    expect($journalEntry->total_debit->isEqualTo($expectedTotal))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedTotal))->toBeTrue();
    expect($journalEntry->is_posted)->toBeTrue();

    // Assert: The journal entry has two lines.
    $this->assertDatabaseCount('journal_entry_lines', 2);

    // Assert: The correct account was debited (Expense).
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $expenseAccount->id,
        'debit' => 150000,
        'credit' => 0,
    ]);

    // Assert: The correct account was credited (Accounts Payable).
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $payableAccount->id,
        'debit' => 0,
        'credit' => 150000,
    ]);
});

test('a posted vendor bill cannot be updated', function () {
    // Arrange: Create a posted vendor bill.
    $currencyCode = $this->company->currency->code;
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'status' => 'posted',
        'total_amount' => Money::of(100, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);
    $originalVendorId = $vendorBill->vendor_id;

    // Arrange: Prepare the data for the update attempt.
    $updateData = ['partner_id' => Partner::factory()->for($this->company)->create()->id];

    // Assert: Expect the service to throw our specific exception.
    expect(fn() => (app(VendorBillService::class))->update($vendorBill, $updateData, $this->user))
        ->toThrow(UpdateNotAllowedException::class, 'Cannot update a posted vendor bill.');

    // Assert: Double-check that the data was not changed.
    $this->assertDatabaseHas('vendor_bills', [
        'id' => $vendorBill->id,
        'vendor_id' => $originalVendorId,
    ]);
});

test('a posted vendor bill cannot be deleted', function () {
    // Arrange: Create a posted vendor bill.
    $currencyCode = $this->company->currency->code;
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'status' => 'posted',
        'total_amount' => Money::of(100, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);

    // Assert: Expect the service's delete method to throw our specific exception.
    expect(fn() => (app(VendorBillService::class))->delete($vendorBill))
        ->toThrow(DeletionNotAllowedException::class, 'Cannot delete a posted vendor bill.');

    // Assert: Confirm the model still exists.
    $this->assertModelExists($vendorBill);
});

test('a draft vendor bill can be deleted', function () {
    // Arrange: Create a draft vendor bill.
    $currencyCode = $this->company->currency->code;
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'status' => 'draft',
        'total_amount' => Money::of(100, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);

    // Act: Call the delete method on the service.
    $wasDeleted = (app(VendorBillService::class))->delete($vendorBill);

    // Assert: The service should report success.
    expect($wasDeleted)->toBeTrue();

    // Assert: The model should be gone from the database.
    $this->assertModelMissing($vendorBill);
});

test('a vendor bill cannot be created or posted in a locked period', function () {
    // Arrange: Lock the company's books.
    LockDate::factory()->for($this->company)->create(['locked_until' => now()->subDay()]);

    // Arrange: Prepare data with a date in the locked period.
    $vendorBillData = [
        'company_id' => $this->company->id,
        'vendor_id' => Partner::factory()->for($this->company)->create()->id,
        'currency_id' => $this->company->currency_id,
        'bill_date' => now()->subMonth()->toDateString(), // Locked date.
        'due_date' => now()->addMonth()->toDateString(),
        'status' => 'draft',
    ];

    // Assert: Expect creation to fail.
    expect(fn() => (app(VendorBillService::class))->create($vendorBillData))
        ->toThrow(PeriodIsLockedException::class);

    // Arrange: Create a draft bill with a valid date.
    $currencyCode = $this->company->currency->code;
    $draftVendorBill = VendorBill::factory()->for($this->company)->create([
        'status' => 'draft',
        'bill_date' => now()->addDay()->toDateString(),
        'total_amount' => Money::of(100, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);

    // Act: Change the date to be inside the locked period before confirming.
    $draftVendorBill->bill_date = now()->subMonth()->toDateString();

    // Assert: Expect confirmation to fail.
    expect(fn() => (app(VendorBillService::class))->confirm($draftVendorBill, $this->user))
        ->toThrow(PeriodIsLockedException::class);
});
