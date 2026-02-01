<?php

namespace Kezi\Payment\Tests\Feature\Traits;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Partner;
use Kezi\Payment\Enums\Payments\PaymentStatus;
use Kezi\Payment\Models\Payment;
use Kezi\Payment\Models\PaymentDocumentLink;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('HasPaymentState trait works correctly with Invoice model', function () {
    $invoice = Invoice::factory()->for($this->company)->create([
        'total_amount' => Money::of(100000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'status' => InvoiceStatus::Posted,
    ]);

    // Test initial state
    expect($invoice->paymentState)->toBe(\Kezi\Foundation\Enums\Shared\PaymentState::NotPaid)
        ->and($invoice->isNotPaid())->toBeTrue()
        ->and($invoice->getPaidAmount())->toEqual(Money::of(0, $this->company->currency->code))
        ->and($invoice->getRemainingAmount())->toEqual(Money::of(100000, $this->company->currency->code));

    // Add partial payment
    $customer = Partner::factory()->for($this->company)->create(['type' => 'customer']);
    $journal = Journal::factory()->for($this->company)->create(['type' => 'bank']);

    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(50000, $this->company->currency->code),
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

    expect($invoice->paymentState)->toBe(\Kezi\Foundation\Enums\Shared\PaymentState::PartiallyPaid)
        ->and($invoice->isPartiallyPaid())->toBeTrue()
        ->and($invoice->getPaidAmount())->toEqual(Money::of(50000, $this->company->currency->code))
        ->and($invoice->getRemainingAmount())->toEqual(Money::of(50000, $this->company->currency->code));
});

test('HasPaymentState trait works correctly with VendorBill model', function () {
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'total_amount' => Money::of(200000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'status' => VendorBillStatus::Posted,
    ]);

    // Test initial state
    expect($vendorBill->paymentState)->toBe(\Kezi\Foundation\Enums\Shared\PaymentState::NotPaid)
        ->and($vendorBill->isNotPaid())->toBeTrue()
        ->and($vendorBill->getPaidAmount())->toEqual(Money::of(0, $this->company->currency->code))
        ->and($vendorBill->getRemainingAmount())->toEqual(Money::of(200000, $this->company->currency->code));

    // Add full payment
    $vendor = Partner::factory()->for($this->company)->create(['type' => 'vendor']);
    $journal = Journal::factory()->for($this->company)->create(['type' => 'bank']);

    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(200000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $vendor->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'vendor_bill_id' => $vendorBill->id,
        'amount_applied' => Money::of(200000, $this->company->currency->code),
    ]);

    $vendorBill->refresh();

    expect($vendorBill->paymentState)->toBe(\Kezi\Foundation\Enums\Shared\PaymentState::Paid)
        ->and($vendorBill->isFullyPaid())->toBeTrue()
        ->and($vendorBill->getPaidAmount())->toEqual(Money::of(200000, $this->company->currency->code))
        ->and($vendorBill->getRemainingAmount())->toEqual(Money::of(0, $this->company->currency->code));
});

test('HasPaymentState trait handles overpayment correctly', function () {
    $invoice = Invoice::factory()->for($this->company)->create([
        'total_amount' => Money::of(100000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'status' => InvoiceStatus::Posted,
    ]);

    $customer = Partner::factory()->for($this->company)->create(['type' => 'customer']);
    $journal = Journal::factory()->for($this->company)->create(['type' => 'bank']);

    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(150000, $this->company->currency->code), // Overpayment
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(150000, $this->company->currency->code),
    ]);

    $invoice->refresh();

    expect($invoice->paymentState)->toBe(\Kezi\Foundation\Enums\Shared\PaymentState::Paid)
        ->and($invoice->isFullyPaid())->toBeTrue()
        ->and($invoice->getPaidAmount())->toEqual(Money::of(150000, $this->company->currency->code))
        ->and($invoice->getRemainingAmount())->toEqual(Money::of(0, $this->company->currency->code)); // Should not be negative
});

test('HasPaymentState trait helper methods work correctly', function () {
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'total_amount' => Money::of(300000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'status' => VendorBillStatus::Posted,
    ]);

    // Test not paid state
    expect($vendorBill->isNotPaid())->toBeTrue()
        ->and($vendorBill->isPartiallyPaid())->toBeFalse()
        ->and($vendorBill->isFullyPaid())->toBeFalse();

    // Add partial payment
    $vendor = Partner::factory()->for($this->company)->create(['type' => 'vendor']);
    $journal = Journal::factory()->for($this->company)->create(['type' => 'bank']);

    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(100000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $vendor->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'vendor_bill_id' => $vendorBill->id,
        'amount_applied' => Money::of(100000, $this->company->currency->code),
    ]);

    $vendorBill->refresh();

    // Test partially paid state
    expect($vendorBill->isNotPaid())->toBeFalse()
        ->and($vendorBill->isPartiallyPaid())->toBeTrue()
        ->and($vendorBill->isFullyPaid())->toBeFalse();

    // Add remaining payment
    $payment2 = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(200000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'paid_to_from_partner_id' => $vendor->id,
        'journal_id' => $journal->id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment2->id,
        'vendor_bill_id' => $vendorBill->id,
        'amount_applied' => Money::of(200000, $this->company->currency->code),
    ]);

    $vendorBill->refresh();

    // Test fully paid state
    expect($vendorBill->isNotPaid())->toBeFalse()
        ->and($vendorBill->isPartiallyPaid())->toBeFalse()
        ->and($vendorBill->isFullyPaid())->toBeTrue();
});

test('HasPaymentState trait uses efficient queries', function () {
    $invoice = Invoice::factory()->for($this->company)->create([
        'total_amount' => Money::of(100000, $this->company->currency->code),
        'currency_id' => $this->company->currency->id,
        'status' => InvoiceStatus::Posted,
    ]);

    // Ensure payments relationship is not loaded
    expect($invoice->relationLoaded('payments'))->toBeFalse();

    // Access payment state - should trigger efficient loadSum query
    $paymentState = $invoice->paymentState;

    expect($paymentState)->toBe(\Kezi\Foundation\Enums\Shared\PaymentState::NotPaid)
        ->and($invoice->paid_amount_sum ?? 0)->toBe(0);

    // Accessing again should not trigger another query since the sum is already loaded
    $paymentState2 = $invoice->paymentState;
    expect($paymentState2)->toBe(\Kezi\Foundation\Enums\Shared\PaymentState::NotPaid);
});
