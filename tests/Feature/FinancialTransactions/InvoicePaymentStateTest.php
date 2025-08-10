<?php

namespace Tests\Feature\FinancialTransactions;

use App\Enums\Accounting\JournalType;
use App\Enums\Partners\PartnerType;
use Brick\Money\Money;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Partner;
use App\Models\Journal;
use App\Models\PaymentDocumentLink;
use App\Enums\Shared\PaymentState;
use App\Enums\Sales\InvoiceStatus;
use App\Enums\Payments\PaymentStatus;
use Tests\Traits\WithConfiguredCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('invoice payment state is not paid when no payments exist', function () {
    $invoice = Invoice::factory()->for($this->company)->create([
        'total_amount' => Money::of(100000, $this->company->currency->code), // 1000.00
        'currency_id' => $this->company->currency->id,
        'status' => InvoiceStatus::Posted,
    ]);

    expect($invoice->paymentState)->toBe(PaymentState::NotPaid)
        ->and($invoice->isNotPaid())->toBeTrue()
        ->and($invoice->isPartiallyPaid())->toBeFalse()
        ->and($invoice->isFullyPaid())->toBeFalse()
        ->and($invoice->getPaidAmount())->toEqual(Money::of(0, $this->company->currency->code))
        ->and($invoice->getRemainingAmount())->toEqual(Money::of(100000, $this->company->currency->code));
});

test('invoice payment state is partially paid when payment is less than total', function () {
    $invoice = Invoice::factory()->for($this->company)->create([
        'total_amount' => Money::of(100000, $this->company->currency->code), // 1000.00
        'currency_id' => $this->company->currency->id,
        'status' => InvoiceStatus::Posted,
    ]);

    $customer = Partner::factory()->for($this->company)->create(['type' => 'customer']);
    $journal = Journal::factory()->for($this->company)->create(['type' => 'bank']);

    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(50000, $this->company->currency->code), // 500.00
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(50000, $this->company->currency->code),
    ]);

    $invoice->refresh();

    expect($invoice->paymentState)->toBe(PaymentState::PartiallyPaid)
        ->and($invoice->isNotPaid())->toBeFalse()
        ->and($invoice->isPartiallyPaid())->toBeTrue()
        ->and($invoice->isFullyPaid())->toBeFalse()
        ->and($invoice->getPaidAmount())->toEqual(Money::of(50000, $this->company->currency->code))
        ->and($invoice->getRemainingAmount())->toEqual(Money::of(50000, $this->company->currency->code));
});

test('invoice payment state is paid when payment equals total amount', function () {
    $invoice = Invoice::factory()->for($this->company)->create([
        'total_amount' => Money::of(100000, $this->company->currency->code), // 1000.00
        'currency_id' => $this->company->currency->id,
        'status' => InvoiceStatus::Posted,
    ]);

    $customer = Partner::factory()->for($this->company)->create(['type' => 'customer']);
    $journal = Journal::factory()->for($this->company)->create(['type' => 'bank']);

    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(100000, $this->company->currency->code), // 1000.00
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(100000, $this->company->currency->code),
    ]);

    $invoice->refresh();

    expect($invoice->paymentState)->toBe(PaymentState::Paid)
        ->and($invoice->isNotPaid())->toBeFalse()
        ->and($invoice->isPartiallyPaid())->toBeFalse()
        ->and($invoice->isFullyPaid())->toBeTrue()
        ->and($invoice->getPaidAmount())->toEqual(Money::of(100000, $this->company->currency->code))
        ->and($invoice->getRemainingAmount())->toEqual(Money::of(0, $this->company->currency->code));
});

test('invoice payment state is paid when overpaid', function () {
    $invoice = Invoice::factory()->for($this->company)->create([
        'total_amount' => Money::of(100000, $this->company->currency->code), // 1000.00
        'currency_id' => $this->company->currency->id,
        'status' => InvoiceStatus::Posted,
    ]);

    $customer = Partner::factory()->for($this->company)->create(['type' => 'customer']);
    $journal = Journal::factory()->for($this->company)->create(['type' => 'bank']);

    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(120000, $this->company->currency->code), // 1200.00
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(120000, $this->company->currency->code),
    ]);

    $invoice->refresh();

    expect($invoice->paymentState)->toBe(PaymentState::Paid)
        ->and($invoice->isNotPaid())->toBeFalse()
        ->and($invoice->isPartiallyPaid())->toBeFalse()
        ->and($invoice->isFullyPaid())->toBeTrue()
        ->and($invoice->getPaidAmount())->toEqual(Money::of(120000, $this->company->currency->code))
        ->and($invoice->getRemainingAmount())->toEqual(Money::of(0, $this->company->currency->code)); // Remaining should be 0, not negative
});

test('invoice payment state handles multiple partial payments correctly', function () {
    $invoice = Invoice::factory()->for($this->company)->create([
        'total_amount' => Money::of(150000, $this->company->currency->code), // 1500.00
        'currency_id' => $this->company->currency->id,
        'status' => InvoiceStatus::Posted,
    ]);

    $customer = Partner::factory()->for($this->company)->create(['type' => 'customer']);
    $journal = Journal::factory()->for($this->company)->create(['type' => 'bank']);

    // First payment: 500.00
    $payment1 = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(50000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment1->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(50000, $this->company->currency->code),
    ]);

    $invoice->refresh();
    expect($invoice->paymentState)->toBe(PaymentState::PartiallyPaid);

    // Second payment: 700.00 (total now 1200.00)
    $payment2 = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(70000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment2->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(70000, $this->company->currency->code),
    ]);

    $invoice->refresh();
    expect($invoice->paymentState)->toBe(PaymentState::PartiallyPaid)
        ->and($invoice->getPaidAmount())->toEqual(Money::of(120000, $this->company->currency->code))
        ->and($invoice->getRemainingAmount())->toEqual(Money::of(30000, $this->company->currency->code));

    // Third payment: 300.00 (total now 1500.00 - fully paid)
    $payment3 = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(30000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment3->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(30000, $this->company->currency->code),
    ]);

    $invoice->refresh();
    expect($invoice->paymentState)->toBe(PaymentState::Paid)
        ->and($invoice->getPaidAmount())->toEqual(Money::of(150000, $this->company->currency->code))
        ->and($invoice->getRemainingAmount())->toEqual(Money::of(0, $this->company->currency->code));
});

test('invoice payment state calculation uses efficient database queries', function () {
    $invoice = Invoice::factory()->for($this->company)->create([
        'total_amount' => Money::of(100000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'status' => InvoiceStatus::Posted,
    ]);

    // Access payment state without loading payments relationship
    expect($invoice->relationLoaded('payments'))->toBeFalse();

    $paymentState = $invoice->paymentState;

    // The trait should have loaded the sum efficiently
    expect($paymentState)->toBe(PaymentState::NotPaid)
        ->and($invoice->paid_amount_sum ?? 0)->toBe(0);
});

test('invoice payment state ignores draft payments per accounting principles', function () {
    $invoice = Invoice::factory()->for($this->company)->create([
        'total_amount' => Money::of(100000, $this->company->currency->code), // 1000.00
        'currency_id' => $this->company->currency->id,
        'status' => InvoiceStatus::Posted,
    ]);

    $customer = Partner::factory()->for($this->company)->create(['type' => PartnerType::Customer]);
    $journal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    // Create a confirmed payment for 500.00
    $confirmedPayment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(50000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $confirmedPayment->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(50000, $this->company->currency->code),
    ]);

    // Create a draft payment for 600.00 (this should be ignored)
    $draftPayment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(60000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Draft, // This should be ignored per accounting principles
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $draftPayment->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(60000, $this->company->currency->code),
    ]);

    $invoice->refresh();

    // Only the confirmed payment should count, draft payment is ignored
    expect($invoice->paymentState)->toBe(PaymentState::PartiallyPaid)
        ->and($invoice->isPartiallyPaid())->toBeTrue()
        ->and($invoice->getPaidAmount())->toEqual(Money::of(50000, $this->company->currency->code)) // Only confirmed payment
        ->and($invoice->getRemainingAmount())->toEqual(Money::of(50000, $this->company->currency->code)); // 1000 - 500 = 500
});

test('invoice payment state treats reconciled payments same as confirmed', function () {
    $invoice = Invoice::factory()->for($this->company)->create([
        'total_amount' => Money::of(100000, $this->company->currency->code), // 1000.00
        'currency_id' => $this->company->currency->id,
        'status' => InvoiceStatus::Posted,
    ]);

    $customer = Partner::factory()->for($this->company)->create(['type' => PartnerType::Customer]);
    $journal = Journal::factory()->for($this->company)->create(['type' => JournalType::Bank]);

    // Create a reconciled payment for full amount
    $reconciledPayment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(100000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Reconciled, // This should count same as confirmed
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $reconciledPayment->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(100000, $this->company->currency->code),
    ]);

    $invoice->refresh();

    // Reconciled payment should be treated same as confirmed
    expect($invoice->paymentState)->toBe(PaymentState::Paid)
        ->and($invoice->isFullyPaid())->toBeTrue()
        ->and($invoice->getPaidAmount())->toEqual(Money::of(100000, $this->company->currency->code))
        ->and($invoice->getRemainingAmount())->toEqual(Money::of(0, $this->company->currency->code));
});
