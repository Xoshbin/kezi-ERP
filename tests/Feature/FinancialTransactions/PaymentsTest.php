<?php

use App\Models\Account;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->company = $this->createConfiguredCompany();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
});

test('an inbound payment can be created and linked to an invoice', function () {
    // Arrange: Create an invoice to be paid.
    $invoice = Invoice::factory()->for($this->company)->create(['status' => 'posted', 'total_amount' => 200]);

    // Arrange: Prepare the payment data.
    $paymentData = [
        'company_id' => $this->company->id,
        'partner_id' => $invoice->customer_id,
        'payment_date' => now()->toDateString(),
        'amount' => 200.00,
        'payment_method' => 'cash',
        'journal_id' => Journal::factory()->for($this->company)->create(['type' => 'Bank'])->id,
        'documents' => [
            ['document_id' => $invoice->id, 'document_type' => 'invoice', 'amount' => 200.00],
        ],
    ];

    // Act: Create the payment using the service.
    $payment = (app(PaymentService::class))->create($paymentData, $this->user);

    // Assert: The payment was created successfully.
    $this->assertModelExists($payment);
    expect($payment->amount)->toEqual(20000);
    expect($payment->payment_type)->toBe(Payment::TYPE_INBOUND);

    // Assert: The payment is correctly linked to the invoice.
    $this->assertDatabaseCount('payment_document_links', 1);
    $this->assertDatabaseHas('payment_document_links', [
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
    ]);
});

test('an outbound payment can be created and linked to a vendor bill', function () {
    // Arrange: Create a vendor bill to be paid.
    $vendorBill = VendorBill::factory()->for($this->company)->create(['status' => 'posted', 'total_amount' => 150]);

    // Arrange: Prepare the payment data.
    $paymentData = [
        'company_id' => $this->company->id,
        'partner_id' => $vendorBill->vendor_id,
        'payment_date' => now()->toDateString(),
        'amount' => 150.00,
        'payment_method' => 'cash',
        'journal_id' => Journal::factory()->for($this->company)->create(['type' => 'Bank'])->id,
        'documents' => [
            ['document_id' => $vendorBill->id, 'document_type' => 'vendor_bill', 'amount' => 150.00],
        ],
    ];

    // Act: Create the payment using the service.
    $payment = (app(PaymentService::class))->create($paymentData, $this->user);

    // Assert: The payment was created successfully.
    $this->assertModelExists($payment);
    expect($payment->amount)->toEqual(15000);
    expect($payment->payment_type)->toBe(Payment::TYPE_OUTBOUND);

    // Assert: The payment is correctly linked to the vendor bill.
    $this->assertDatabaseCount('payment_document_links', 1);
    $this->assertDatabaseHas('payment_document_links', [
        'payment_id' => $payment->id,
        'vendor_bill_id' => $vendorBill->id,
    ]);
});

test('creating a payment generates the correct journal entry', function () {
    // Arrange: Set up the necessary accounts and journal.
    $receivableAccount = Account::factory()->for($this->company)->create(['type' => 'Receivable']);
    $bankAccount = Account::factory()->for($this->company)->create(['type' => 'Bank']);
    $bankJournal = Journal::factory()->for($this->company)->create(['type' => 'Bank', 'default_debit_account_id' => $bankAccount->id, 'default_credit_account_id' => $bankAccount->id]);
    $this->company->update(['default_accounts_receivable_id' => $receivableAccount->id]);

    // Arrange: Create a posted invoice.
    $invoice = Invoice::factory()->for($this->company)->create(['status' => 'posted', 'total_amount' => 500]);

    // Arrange: Prepare payment data for the full amount.
    $paymentData = [
        'company_id' => $this->company->id,
        'partner_id' => $invoice->partner_id,
        'payment_date' => now()->toDateString(),
        'amount' => 500.00,
        'payment_method' => 'cash',
        'journal_id' => $bankJournal->id,
        'documents' => [
            ['document_id' => $invoice->id, 'document_type' => 'invoice', 'amount' => 500.00],
        ],
    ];

    // Act: Create the payment.
    $payment = (app(PaymentService::class))->create($paymentData, $this->user);
    // Act: Confirm the payment to trigger journal entry creation.
    (app(PaymentService::class))->confirm($payment, $this->user);

    // Assert: A journal entry was created and is linked to the payment.
    $this->assertNotNull($payment->fresh()->journal_entry_id);
    $journalEntry = $payment->journalEntry;
    $this->assertModelExists($journalEntry);

    // Assert: The journal entry has the correct details.
    expect($journalEntry->journal_id)->toBe($bankJournal->id);
    expect($journalEntry->total_debit)->toEqual(50000);
    expect($journalEntry->total_credit)->toEqual(50000);
    expect($journalEntry->is_posted)->toBeTrue();

    // Assert: The correct account was debited (Bank).
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $bankAccount->id,
        'debit' => 50000,
        'credit' => 0,
    ]);

    // Assert: The correct account was credited (Accounts Receivable).
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $receivableAccount->id,
        'debit' => 0,
        'credit' => 50000,
    ]);
});