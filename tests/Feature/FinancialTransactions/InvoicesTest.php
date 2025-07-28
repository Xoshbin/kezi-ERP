<?php

use App\Events\InvoiceConfirmed;
use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\PeriodIsLockedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Account;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\LockDate;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
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

test('a draft invoice can be confirmed, which posts it and dispatches an event', function () {
    // Arrange: Ensure events are being listened for.
    Event::fake();

    // Arrange: Create a draft invoice with all required data.
    $currencyCode = $this->company->currency->code;
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'draft',
        'total_amount' => Money::of(0, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);

    // Act: Call the confirm method on the service.
    (app(InvoiceService::class))->confirm($invoice, $this->user);

    // Assert: The invoice's status is now 'posted'.
    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'status' => 'posted',
    ]);

    // Assert: An event was dispatched to notify other parts of the system.
    Event::assertDispatched(InvoiceConfirmed::class, function ($event) use ($invoice) {
        return $event->invoice->id === $invoice->id;
    });
});

test('confirming an invoice generates the correct journal entry', function () {
    // Arrange: The company is already configured with default accounts and journals.
    $productSalesAccount = Account::factory()->for($this->company)->create(['type' => 'Income']);
    $currencyCode = $this->company->currency->code;
    $product = Product::factory()->for($this->company)->create([
        'income_account_id' => $productSalesAccount->id,
        'unit_price' => Money::of(100, $currencyCode)
    ]);

    // Arrange: Create a draft invoice with one line item, explicitly passing the configured company and currency.
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'draft',
        'total_amount' => Money::of(0, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);
    $invoice->invoiceLines()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => Money::of(100, $currencyCode), // Unit price is $100.00
    ]);

    // Act: Confirm the invoice.
    (app(InvoiceService::class))->confirm($invoice, $this->user);

    // Assert: A single journal entry was created.
    $this->assertDatabaseCount('journal_entries', 1);

    // Assert: The journal entry has the correct details.
    $journalEntry = JournalEntry::first();
    expect($journalEntry->journal_id)->toBe($this->company->default_sales_journal_id);
    $expectedTotal = Money::of(200, $currencyCode);
    expect($journalEntry->total_debit->isEqualTo($expectedTotal))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedTotal))->toBeTrue();
    expect($journalEntry->is_posted)->toBeTrue();

    // Assert: The journal entry has two lines, one for debit and one for credit.
    $this->assertDatabaseCount('journal_entry_lines', 2);

    // Assert: The correct account was debited (Accounts Receivable).
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_accounts_receivable_id,
        'debit' => 200000,
        'credit' => 0,
    ]);

    // Assert: The correct account was credited (Product Sales).
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $productSalesAccount->id,
        'debit' => 0,
        'credit' => 200000,
    ]);
});

test('a posted invoice cannot be updated', function () {
    // Arrange: Create a posted invoice.
    $currencyCode = $this->company->currency->code;
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'posted',
        'total_amount' => Money::of(100, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);
    $originalCustomerId = $invoice->customer_id;

    // Arrange: Prepare the data for the update attempt.
    $updateData = ['customer_id' => Partner::factory()->for($this->company)->create()->id];

    // Assert: Expect the service to throw our specific exception.
    expect(fn() => (app(InvoiceService::class))->update($invoice, $updateData))
        ->toThrow(UpdateNotAllowedException::class, 'Cannot modify a non-draft invoice.');

    // Assert: Double-check that the customer_id was not changed in the database.
    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'customer_id' => $originalCustomerId,
    ]);
});

test('a posted invoice cannot be deleted', function () {
    // Arrange: Create a posted invoice.
    $currencyCode = $this->company->currency->code;
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'posted',
        'total_amount' => Money::of(100, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);

    // Assert: Expect the service's delete method to throw our specific exception.
    expect(fn() => (app(InvoiceService::class))->delete($invoice))
        ->toThrow(DeletionNotAllowedException::class, 'Cannot delete a posted invoice.');

    // Assert: As a final check, confirm the model still exists.
    $this->assertModelExists($invoice);
});

test('a draft invoice can be deleted', function () {
    // Arrange: Create a draft invoice.
    $currencyCode = $this->company->currency->code;
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'draft',
        'total_amount' => Money::of(100, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);

    // Act: Call the delete method on the service.
    $wasDeleted = (app(InvoiceService::class))->delete($invoice);

    // Assert: The service should report that the deletion was successful.
    expect($wasDeleted)->toBeTrue();

    // Assert: The model should no longer exist in the database.
    $this->assertModelMissing($invoice);
});

test('an invoice cannot be created or posted in a locked period', function () {
    // Arrange: Lock the company's books for any date in the past.
    LockDate::factory()->for($this->company)->create(['locked_until' => now()->subDay()]);

    // Arrange: Prepare invoice data with a date that falls within the locked period.
    $invoiceData = [
        'company_id' => $this->company->id,
        'customer_id' => Partner::factory()->for($this->company)->create()->id,
        'currency_id' => $this->company->currency_id,
        'invoice_date' => now()->subMonth()->toDateString(), // This date is locked.
        'due_date' => now()->addMonth()->toDateString(),
        'status' => 'draft',
    ];

    // Assert: Expect that trying to CREATE an invoice in a locked period fails.
    expect(fn() => (app(InvoiceService::class))->create($invoiceData))
        ->toThrow(PeriodIsLockedException::class, 'The period for this invoice date is locked.');

    // Arrange: Create a draft invoice with a date in the future (not locked).
    $currencyCode = $this->company->currency->code;
    $draftInvoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'status' => 'draft',
        'invoice_date' => now()->addDay()->toDateString(),
        'total_amount' => Money::of(100, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);

    // Act: Now, try to CONFIRM the invoice but set its date to be inside the locked period.
    // This simulates a user changing the date before confirming.
    $draftInvoice->invoice_date = now()->subMonth()->toDateString();

    // Assert: Expect that trying to CONFIRM an invoice in a locked period also fails.
    expect(fn() => (app(InvoiceService::class))->confirm($draftInvoice, $this->user))
        ->toThrow(PeriodIsLockedException::class, 'The period for this invoice date is locked.');
});