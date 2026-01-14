<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Partner;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentDocumentLink;
use Modules\Payment\Services\PaymentService;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('payment confirmation properly posts draft invoice before marking as paid', function () {
    // Arrange: Create a draft invoice
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    $invoiceAmount = Money::of(100000, $this->company->currency->code);
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'status' => InvoiceStatus::Draft, // Important: starts as draft
        'total_amount' => $invoiceAmount,
        'invoice_number' => null, // Draft invoices don't have numbers
        'posted_at' => null,
        'journal_entry_id' => null,
    ]);

    // Create a matching line to prevent recomputation issues
    \Modules\Sales\Models\InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'company_id' => $this->company->id,
        'subtotal' => $invoiceAmount,
        'total_line_tax' => Money::zero($this->company->currency->code),
    ]);

    // Create a payment for the full amount
    $journal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Draft,
        'amount' => $invoiceAmount,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $customer->id,
        'payment_type' => PaymentType::Inbound,
        'journal_id' => $journal->id,
    ]);

    // Link payment to invoice
    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => $invoiceAmount,
    ]);

    // Act: Confirm the payment
    $paymentService = app(PaymentService::class);
    $paymentService->confirm($payment, $this->user);

    // Assert: Invoice should be properly posted first, then marked as paid
    $invoice->refresh();

    // Verify the invoice went through proper posting process
    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->invoice_number)->not->toBeNull() // Should have been assigned during posting
        ->and($invoice->posted_at)->not->toBeNull() // Should have posted_at timestamp
        ->and($invoice->journal_entry_id)->not->toBeNull(); // Should have journal entry

    // Verify journal entry was created and posted
    $journalEntry = $invoice->journalEntry;
    expect($journalEntry)->not->toBeNull()
        ->and($journalEntry->is_posted)->toBeTrue();

    // Verify payment is confirmed
    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Confirmed)
        ->and($payment->journal_entry_id)->not->toBeNull();
});

test('payment confirmation properly posts draft vendor bill before marking as paid', function () {
    // Arrange: Create a draft vendor bill
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $billAmount = Money::of(50000, $this->company->currency->code);
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $this->company->currency_id,
        'status' => VendorBillStatus::Draft, // Important: starts as draft
        'total_amount' => $billAmount,
        'journal_entry_id' => null,
        'posted_at' => null,
    ]);

    // Create a matching line to prevent recomputation issues
    \Modules\Purchase\Models\VendorBillLine::factory()->create([
        'vendor_bill_id' => $vendorBill->id,
        'company_id' => $this->company->id,
        'subtotal' => $billAmount,
        'total_line_tax' => Money::zero($this->company->currency->code),
    ]);

    // Create a payment for the full amount
    $journal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Draft,
        'amount' => $billAmount,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $vendor->id,
        'payment_type' => PaymentType::Outbound,
        'journal_id' => $journal->id,
    ]);

    // Link payment to vendor bill
    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'vendor_bill_id' => $vendorBill->id,
        'amount_applied' => $billAmount,
    ]);

    // Act: Confirm the payment
    $paymentService = app(PaymentService::class);
    $paymentService->confirm($payment, $this->user);

    // Assert: Vendor bill should be properly posted first, then marked as paid
    $vendorBill->refresh();

    // Verify the vendor bill went through proper posting process
    expect($vendorBill->status)->toBe(VendorBillStatus::Paid)
        ->and($vendorBill->posted_at)->not->toBeNull() // Should have posted_at timestamp
        ->and($vendorBill->journal_entry_id)->not->toBeNull(); // Should have journal entry

    // Verify journal entry was created and posted
    $journalEntry = $vendorBill->journalEntry;
    expect($journalEntry)->not->toBeNull()
        ->and($journalEntry->is_posted)->toBeTrue();

    // Verify payment is confirmed
    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Confirmed)
        ->and($payment->journal_entry_id)->not->toBeNull();
});

test('payment confirmation does not affect already posted documents', function () {
    // Arrange: Create an already posted invoice
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    $invoiceAmount = Money::of(100000, $this->company->currency->code);
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'status' => InvoiceStatus::Posted, // Already posted
        'total_amount' => $invoiceAmount,
        'invoice_number' => 'INV-001',
        'posted_at' => now(),
    ]);

    // Create a payment for the full amount
    $journal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);
    $payment = Payment::factory()->create([
        'company_id' => $this->company->id,
        'status' => PaymentStatus::Draft,
        'amount' => $invoiceAmount,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $customer->id,
        'payment_type' => PaymentType::Inbound,
        'journal_id' => $journal->id,
    ]);

    // Link payment to invoice
    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => $invoiceAmount,
    ]);

    // Store original values
    $originalInvoiceNumber = $invoice->invoice_number;
    $originalPostedAt = $invoice->posted_at;

    // Act: Confirm the payment
    $paymentService = app(PaymentService::class);
    $paymentService->confirm($payment, $this->user);

    // Assert: Invoice should be marked as paid without changing posting details
    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->invoice_number)->toBe($originalInvoiceNumber) // Should not change
        ->and($invoice->posted_at->toDateTimeString())->toBe($originalPostedAt->toDateTimeString()); // Should not change
});
