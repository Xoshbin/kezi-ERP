<?php

use App\Models\User;
use Brick\Money\Money;
use App\Models\Account;
use App\Models\Company;
use App\Enums\Accounting\JournalType;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\VendorBill;
use Tests\Traits\MocksTime;
use App\Services\PaymentService;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithUnlockedPeriod;
use Tests\Traits\WithConfiguredCompany;
use App\Actions\Payments\CreatePaymentAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;

uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

test('an inbound payment can be created and linked to an invoice', function () {
    // Arrange: Create an invoice to be paid.
    $currencyCode = $this->company->currency->code;
    $invoice = Invoice::factory()->for($this->company)->create([
        'status' => 'posted',
        'total_amount' => Money::of(200, $currencyCode),
    ]);

    // Arrange: Prepare the DTOs for the Action.
    $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
        document_type: 'invoice',
        document_id: $invoice->id,
        amount_applied: '200.00'
    );

    $paymentDTO = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: Journal::factory()->for($this->company)->create(['type' => JournalType::Bank])->id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        document_links: [$documentLinkDTO],
        reference: null
    );

    // Act: Create the payment using the Action.
    $payment = (app(CreatePaymentAction::class))->execute($paymentDTO, $this->user);

    // Assert: The payment was created successfully.
    $this->assertModelExists($payment);
    expect($payment->amount->isEqualTo(Money::of(200, $currencyCode)))->toBeTrue();
    expect($payment->payment_type)->toBe(Payment::TYPE_INBOUND);

    // Assert: The payment is correctly linked to the invoice.
    $this->assertDatabaseHas('payment_document_links', [
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
    ]);
});


test('an outbound payment can be created and linked to a vendor bill', function () {
    // Arrange: Create a vendor bill to be paid.
    $currencyCode = $this->company->currency->code;
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'status' => 'posted',
        'total_amount' => Money::of(150, $currencyCode),
    ]);

    // Arrange: Prepare the DTOs for the Action.
    $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
        document_type: 'vendor_bill',
        document_id: $vendorBill->id,
        amount_applied: '150.00'
    );

    $paymentDTO = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: Journal::factory()->for($this->company)->create(['type' => JournalType::Bank])->id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        document_links: [$documentLinkDTO],
        reference: null
    );

    // Act: Create the payment using the Action.
    $payment = (app(CreatePaymentAction::class))->execute($paymentDTO, $this->user);

    // Assert: The payment was created successfully.
    $this->assertModelExists($payment);
    expect($payment->amount->isEqualTo(Money::of(150, $currencyCode)))->toBeTrue();
    expect($payment->payment_type)->toBe(Payment::TYPE_OUTBOUND);

    // Assert: The payment is correctly linked to the vendor bill.
    $this->assertDatabaseHas('payment_document_links', [
        'payment_id' => $payment->id,
        'vendor_bill_id' => $vendorBill->id,
    ]);
});

test('creating a payment generates the correct journal entry', function () {
    // Arrange: Set up the necessary accounts and journal.
    $receivableAccount = Account::factory()->for($this->company)->create(['type' => 'Receivable']);
    $bankAccount = Account::factory()->for($this->company)->create(['type' => 'Bank']);
    $bankJournal = Journal::factory()->for($this->company)->create([
        'type' => JournalType::Bank,
        'default_debit_account_id' => $bankAccount->id,
        'default_credit_account_id' => $bankAccount->id
    ]);
    $this->company->update(['default_accounts_receivable_id' => $receivableAccount->id]);

    // Arrange: Create a posted invoice.
    $invoice = Invoice::factory()->for($this->company)->create([
        'status' => 'posted',
        'total_amount' => Money::of(500, $this->company->currency->code),
        'currency_id' => $this->company->currency_id,
    ]);

    // Arrange: Prepare the DTOs for the Action.
    $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
        document_type: 'invoice',
        document_id: $invoice->id,
        amount_applied: '500.00'
    );

    $paymentDTO = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $bankJournal->id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        document_links: [$documentLinkDTO],
        reference: 'Test Payment'
    );

    // Act: Create the payment using the Action.
    $payment = (app(CreatePaymentAction::class))->execute($paymentDTO, $this->user);

    // Act: Confirm the payment to trigger journal entry creation.
    (app(PaymentService::class))->confirm($payment, $this->user);

    // Assert: A journal entry was created and is linked to the payment.
    $this->assertNotNull($payment->fresh()->journal_entry_id);
    $journalEntry = $payment->journalEntry;
    $this->assertModelExists($journalEntry);

    // Assert: The journal entry has the correct details.
    $expectedAmount = Money::of(500, $this->company->currency->code);
    expect($journalEntry->total_debit->isEqualTo($expectedAmount))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedAmount))->toBeTrue();
    expect($journalEntry->is_posted)->toBeTrue();

    // Assert: The correct accounts were debited (Bank) and credited (Accounts Receivable).
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $bankAccount->id,
        'debit' => 500000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $receivableAccount->id,
        'credit' => 500000,
    ]);
});
