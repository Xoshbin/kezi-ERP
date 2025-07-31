<?php

namespace Tests\Feature\General;

use App\Models\User;
use RuntimeException;
use Brick\Money\Money;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Product;
use App\Models\VendorBill;
use App\Models\InvoiceLine;
use App\Models\JournalEntry;
use App\Models\VendorBillLine;
use App\Services\InvoiceService;
use Tests\Traits\CreatesApplication;
use Illuminate\Database\QueryException;
use App\Exceptions\DeletionNotAllowedException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, CreatesApplication::class);

/**
 * This test suite specifically targets deletion scenarios that would violate accounting principles,
 * focusing on the integrity of relationships between posted financial documents and their associated master data.
 */
beforeEach(function () {
    // Set up a fully configured company and an authenticated user for each test.
    $this->company = $this->createConfiguredCompany();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
});

//======================================================================
// Test Case 1: Protecting Journal Entry Lines
//======================================================================
test('a journal entry line cannot be deleted from a posted journal entry', function () {
    /**
     * Principle: The individual lines of a posted journal entry are as immutable as the entry itself.
     * Deleting a line would unbalance the entry and destroy the financial record.
     * This is enforced by a 'deleting' event listener on the JournalEntryLine model.
     */

    // Arrange: Create a posted journal entry with balanced lines.
    $currencyCode = $this->company->currency->code;
    $journalEntry = JournalEntry::factory()->for($this->company)->create([
        'is_posted' => true,
        'total_debit' => Money::of(100, $currencyCode),
        'total_credit' => Money::of(100, $currencyCode),
    ]);
    $lineToDelete = $journalEntry->lines()->create([
        'account_id' => Account::factory()->for($this->company)->create()->id,
        'debit' => Money::of(100, $currencyCode),
        'credit' => Money::of(0, $currencyCode),
        'currency_id' => $this->company->currency_id,
    ]);
    $journalEntry->lines()->create([
        'account_id' => Account::factory()->for($this->company)->create()->id,
        'debit' => Money::of(0, $currencyCode),
        'credit' => Money::of(100, $currencyCode),
        'currency_id' => $this->company->currency_id,
    ]);

    // Act & Assert: Attempting to delete the line directly should throw a RuntimeException.
    expect(fn() => $lineToDelete->delete())
        ->toThrow(
            RuntimeException::class,
            'Cannot delete a journal entry line because its parent journal entry is already posted.'
        );

    // Verify: The line must still exist in the database.
    $this->assertModelExists($lineToDelete);
});

//======================================================================
// Test Case 2: Protecting Associated Master Data (Partner)
//======================================================================
test('a partner linked to a posted invoice cannot be deleted', function () {
    /**
     * Principle: Master data (like a customer or vendor) that is part of a posted transaction
     * cannot be deleted, as it would orphan the financial record and break the audit trail.
     * This is enforced by our PartnerObserver.
     */

    // Arrange: Create a partner and a posted invoice linked to them.
    $customer = Partner::factory()->for($this->company)->create();
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $customer->id,
        'status' => 'posted',
    ]);

    // Act & Assert: Attempting to delete the partner should be blocked by our observer.
    expect(fn() => $customer->delete())
        ->toThrow(
            DeletionNotAllowedException::class,
            'Cannot delete a partner with associated financial documents (invoices, bills, or payments).'
        );

    // Verify: The partner must still exist in the database.
    $this->assertModelExists($customer);
});


//======================================================================
// Test Case 3: Protecting Associated Master Data (Product)
//======================================================================
test('a product linked to a posted vendor bill line cannot be deleted', function () {
    /**
     * Principle: Master data like a Product, once used in a transaction,
     * should not be deletable (even soft-deleted) to preserve data integrity.
     */

    // Arrange: Create a product and use it in a vendor bill line.
    $product = Product::factory()->for($this->company)->create();
    $vendorBill = VendorBill::factory()->for($this->company)->create(['status' => 'posted']);
    $vendorBill->lines()->create([
        'description' => 'Test line item for product deletion test', // <-- Add this line
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => Money::of(100, $this->company->currency->code),
        'expense_account_id' => Account::factory()->for($this->company)->create()->id,
    ]);

    // Act & Assert: Attempting to delete the product should be blocked by our new observer.
    expect(fn() => $product->delete())
        ->toThrow(DeletionNotAllowedException::class, 'Cannot delete a product that has been used in transactions.');

    // Verify: The product must not have been soft-deleted.
    $this->assertNotSoftDeleted($product);
});


//======================================================================
// Test Case 4: Protecting the Audit Trail (User)
//======================================================================
test('a user who created a journal entry cannot be deleted', function () {
    /**
     * Principle: The user who creates a financial transaction is a critical part of the audit trail.
     * Deleting the user would make it impossible to know who was responsible for the entry.
     * This is enforced by a database foreign key constraint.
     */

    // Arrange: The `beforeEach` hook already creates a user. We just need to link them to an entry.
    JournalEntry::factory()->for($this->company)->create([
        'created_by_user_id' => $this->user->id,
        'is_posted' => true,
        'total_debit' => 0,
        'total_credit' => 0,
    ]);

    // Act & Assert: Attempting to delete the user should fail due to the database constraint.
    $this->expectException(QueryException::class);
    $this->user->delete();

    // Verify: The user must still exist.
    $this->assertModelExists($this->user);
});

//======================================================================
// Test Case 5: Protecting Posted Documents (Invoice)
//======================================================================
test('a posted invoice with lines cannot be deleted', function () {
    /**
     * Principle: A posted invoice is an immutable financial document. It cannot be deleted.
     * This is enforced by the InvoiceService and model-level logic.
     */

    // Arrange: Create a posted invoice with a line.
    $invoice = Invoice::factory()->for($this->company)->create([
        'status' => 'draft',
        'total_amount' => 100,
        'total_tax' => 0,
    ]);
    InvoiceLine::factory()->for($invoice)->create();
    (new InvoiceService(app(\App\Services\JournalEntryService::class)))->confirm($invoice, $this->user);

    // Act & Assert: Attempting to delete the invoice should be blocked by our application logic.
    $this->expectException(\App\Exceptions\DeletionNotAllowedException::class);
    (new InvoiceService(app(\App\Services\JournalEntryService::class)))->delete($invoice);

    // Verify: The invoice and its journal entry must still exist.
    $this->assertModelExists($invoice);
    $this->assertNotNull($invoice->fresh()->journal_entry_id);
});

