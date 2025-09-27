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
use App\Enums\Sales\InvoiceStatus;
use Illuminate\Database\QueryException;
use Tests\Traits\WithConfiguredCompany;
use App\Exceptions\DeletionNotAllowedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Purchases\CreateVendorBillLineAction;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

// ======================================================================
// Test Case 1: Protecting Journal Entry Lines
// ======================================================================
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
        'currency_id' => $this->company->currency_id, // Ensure currency is set
    ]);
    $lineToDelete = $journalEntry->lines()->create([
        'company_id' => $this->company->id,
        'account_id' => \Modules\Accounting\Models\Account::factory()->for($this->company)->create()->id,
        'debit' => Money::of(100, $currencyCode),
        'credit' => Money::of(0, $currencyCode),
    ]);
    $journalEntry->lines()->create([
        'company_id' => $this->company->id,
        'account_id' => \Modules\Accounting\Models\Account::factory()->for($this->company)->create()->id,
        'debit' => Money::of(0, $currencyCode),
        'credit' => Money::of(100, $currencyCode),
    ]);

    // Act & Assert: Attempting to delete the line directly should throw a RuntimeException.
    expect(fn () => $lineToDelete->delete())
        ->toThrow(
            RuntimeException::class,
            'Cannot delete a journal entry line because its parent journal entry is already posted.'
        );

    // Verify: The line must still exist in the database.
    $this->assertModelExists($lineToDelete);
});

// ======================================================================
// Test Case 2: Protecting Associated Master Data (Partner)
// ======================================================================
test('a partner linked to a posted invoice cannot be deleted', function () {
    /**
     * Principle: Master data (like a customer or vendor) that is part of a posted transaction
     * cannot be deleted, as it would orphan the financial record and break the audit trail.
     * This is enforced by our PartnerObserver.
     */

    // Arrange: Create a partner and a posted invoice linked to them.
    $customer = \Modules\Foundation\Models\Partner::factory()->for($this->company)->create();
    \Modules\Sales\Models\Invoice::factory()->for($this->company)->create([
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id, // Ensure currency is set
        'status' => 'posted',
    ]);

    // Act & Assert: Attempting to delete the partner should be blocked by our observer.
    expect(fn () => $customer->delete())
        ->toThrow(
            \Modules\Foundation\Exceptions\DeletionNotAllowedException::class,
            'Cannot delete a partner with associated financial documents (invoices, bills, or payments).'
        );

    // Verify: The partner must still exist in the database.
    $this->assertModelExists($customer);
});

// ======================================================================
// Test Case 3: Protecting Associated Master Data (Product)
// ======================================================================
test('a product linked to a posted vendor bill line cannot be deleted', function () {
    /**
     * Principle: Master data like a Product, once used in a transaction,
     * should not be deletable (even soft-deleted) to preserve data integrity.
     */

    // Arrange: Create a product and a posted vendor bill.
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create();
    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($this->company)->create([
        'status' => 'posted',
        'currency_id' => $this->company->currency_id,
    ]);

    // Act: Create the vendor bill line using the dedicated Action and DTO.
    // This correctly encapsulates the creation logic and ensures all required fields are calculated.
    $lineDto = new CreateVendorBillLineDTO(
        description: 'Test line item for product deletion test',
        quantity: 1,
        unit_price: '100.00', // The DTO accepts a clean string representation.
        expense_account_id: $product->expense_account_id, // Use the product's default expense account.
        product_id: $product->id,
        tax_id: null,
        analytic_account_id: null
    );

    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);
    // --- END OF FIX ---

    // Assert: Attempting to delete the product should be blocked by the ProductObserver.
    expect(fn () => $product->delete())
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class, 'Cannot delete a product that has been used in transactions.');

    // Verify: The product must not have been soft-deleted.
    $this->assertNotSoftDeleted($product);
});

// ======================================================================
// Test Case 4: Protecting the Audit Trail (User)
// ======================================================================
test('a user who created a journal entry cannot be deleted', function () {
    /**
     * Principle: The user who creates a financial transaction is a critical part of the audit trail.
     * Deleting the user would make it impossible to know who was responsible for the entry.
     * This is enforced by a database foreign key constraint.
     */

    // Arrange: The `beforeEach` hook already creates a user. We just need to link them to an entry.
    JournalEntry::factory()->for($this->company)->create([
        'created_by_user_id' => $this->user->id,
        'currency_id' => $this->company->currency_id, // Ensure currency is set
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

// ======================================================================
// Test Case 5: Protecting Posted Documents (Invoice)
// ======================================================================
test('a posted invoice with lines cannot be deleted', function () {
    /**
     * Principle: A posted invoice is an immutable financial document. It cannot be deleted.
     */

    // Arrange: Create a draft invoice with at least one line to satisfy business rules.
    $invoice = \Modules\Sales\Models\Invoice::factory()->for($this->company)->withLines(1)->create([
        'status' => InvoiceStatus::Draft,
        'currency_id' => $this->company->currency_id,
    ]);

    // Act: Confirm the invoice using the service.
    $this->mock(\App\Services\JournalEntryService::class, function ($mock) {
        $mock->shouldReceive('post')->once();
    });
    $this->mock(\App\Services\Inventory\StockMoveService::class);
    app(\App\Services\InvoiceService::class)->confirm($invoice, $this->user);

    // Assert: Attempting to delete the now-posted invoice must fail.
    $this->expectException(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class);
    app(\App\Services\InvoiceService::class)->delete($invoice);

    // Verify: The invoice and its journal entry must still exist.
    $this->assertModelExists($invoice);
    $this->assertNotNull($invoice->fresh()->journal_entry_id);
});
