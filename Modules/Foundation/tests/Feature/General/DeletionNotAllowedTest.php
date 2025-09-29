<?php

namespace Modules\Foundation\Tests\Feature;

use Modules\Sales\Models\Invoice;
use Modules\Accounting\Models\Tax;
use Modules\Accounting\Models\Asset;
use Modules\Sales\Models\InvoiceLine;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;
use Modules\Accounting\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

// ======================================================================
// Test Case 1: Comprehensive Company Deletion Prevention
// ======================================================================
test('a company with any financial records cannot be deleted', function (string $relatedModel, array $factoryState = []) {
    /**
     * Principle: A legal entity (Company) cannot be removed if it has any financial history.
     * This test uses a data provider to check various types of associated records.
     */

    // Arrange: Create a financial record linked to the company.
    // The data provider will inject the model class to create.
    $relatedModel::factory()->for($this->company)->create($factoryState);

    // Act & Assert: Attempting to delete the company should fail with our specific exception.
    expect(fn() => $this->company->delete())
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class, 'Cannot delete a company with associated financial records.');

    // Verify: The company must still exist in the database.
    $this->assertModelExists($this->company);
})->with([
    'with an Account' => [Account::class],
    'with a Journal Entry' => [JournalEntry::class, ['total_debit' => 0, 'total_credit' => 0]],
    'with an Invoice' => [Invoice::class, ['total_amount' => 0, 'total_tax' => 0]],
    'with a Vendor Bill' => [VendorBill::class, ['total_amount' => 0, 'total_tax' => 0]],
    'with an Asset' => [Asset::class, ['purchase_value' => 1000, 'salvage_value' => 0]],
]);

// ======================================================================
// Test Case 2: Journal Deletion Prevention
// ======================================================================
test('a journal with journal entries cannot be deleted', function () {
    /**
     * Principle: A journal (e.g., Sales Journal, Bank Journal) is a book of original entry.
     * It cannot be deleted if it contains transactions, as this would break the audit trail.
     */

    // Arrange: Create a journal and a journal entry within it.
    $journal = Journal::factory()->for($this->company)->create();
    JournalEntry::factory()->for($this->company)->for($journal)->create(['total_debit' => 0, 'total_credit' => 0]);

    // Act & Assert: Attempting to delete the journal should be blocked.
    expect(fn() => $journal->delete())
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class, 'Cannot delete a journal with associated journal entries.');

    // Verify: The journal must still exist.
    $this->assertModelExists($journal);
});

// ======================================================================
// Test Case 3: Currency Deletion Prevention
// ======================================================================
test('a currency in use by a company or transaction cannot be deleted', function () {
    /**
     * Principle: A currency cannot be deleted if it's the base currency for a company
     * or has been used in any financial transaction.
     */

    // Arrange: The company created by our trait is already using a currency.
    $currencyInUse = $this->company->currency;

    // Act & Assert: Attempting to delete this currency should fail.
    expect(fn() => $currencyInUse->delete())
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class, 'Cannot delete a currency that is in use.');

    // Verify: The currency must still exist.
    $this->assertModelExists($currencyInUse);
});

// ======================================================================
// Test Case 4: Tax Deletion Prevention
// ======================================================================
test('a tax used in a transaction is deactivated instead of deleted', function () {
    /**
     * Principle: Tax rates used in historical transactions must be preserved for auditing.
     * Instead of deletion, the tax record should be marked as inactive to prevent future use.
     */

    // Arrange: Create a tax and use it in an invoice line.
    $tax = Tax::factory()->for($this->company)->create(['is_active' => true]);
    $invoice = Invoice::factory()->for($this->company)->create(['total_amount' => 0, 'total_tax' => 0]);
    InvoiceLine::factory()->for($invoice)->for($tax)->create(['unit_price' => 100, 'quantity' => 1]);

    // Act: Attempt to delete the tax. The observer should intercept this.
    $deleteResult = $tax->delete();

    // Assert: The observer should cancel the deletion.
    expect($deleteResult)->toBeFalse();

    // Verify: The tax record still exists but is now marked as inactive.
    $this->assertDatabaseHas('taxes', [
        'id' => $tax->id,
        'is_active' => false,
    ]);
});
