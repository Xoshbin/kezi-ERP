<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Kezi\Payment\DataTransferObjects\Payments\UpdatePaymentDocumentLinkDTO;
use Kezi\Payment\DataTransferObjects\Payments\UpdatePaymentDTO;
use Kezi\Payment\Enums\Payments\PaymentMethod;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Payment\Models\Payment;
use Kezi\Payment\Models\PaymentDocumentLink;
use Kezi\Payment\Services\Payments\Strategies\SettlementStrategy;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->strategy = new SettlementStrategy;
});

test('it creates payment document links for settlement payment creation', function () {
    // Arrange
    $invoice = Invoice::factory()->for($this->company)->create();
    $payment = Payment::factory()->for($this->company)->create([

        'payment_type' => PaymentType::Inbound,
        'currency_id' => $this->company->currency_id,
    ]);

    $linkDTO = new CreatePaymentDocumentLinkDTO(
        document_type: 'invoice',
        document_id: $invoice->id,
        amount_applied: Money::of(500, $this->company->currency->code)
    );

    $dto = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $payment->journal_id,
        currency_id: $payment->currency_id,
        payment_date: $payment->payment_date->toDateString(),
        // settlement inferred by presence of document links
        payment_type: PaymentType::Inbound,
        payment_method: PaymentMethod::BankTransfer,
        paid_to_from_partner_id: null,
        amount: null,
        document_links: [$linkDTO],
        reference: 'Test Settlement'
    );

    // Act
    $this->strategy->executeCreate($payment, $dto);

    // Assert
    expect($payment->paymentDocumentLinks)->toHaveCount(1);
    $link = $payment->paymentDocumentLinks->first();
    expect($link->invoice_id)->toBe($invoice->id);
    expect($link->vendor_bill_id)->toBeNull();
    expect($link->amount_applied->getAmount()->toFloat())->toBe(500.0);
});

test('it creates payment document links for vendor bill settlement', function () {
    // Arrange
    $vendorBill = VendorBill::factory()->for($this->company)->create();
    $payment = Payment::factory()->for($this->company)->create([

        'payment_type' => PaymentType::Outbound,
        'currency_id' => $this->company->currency_id,
    ]);

    $linkDTO = new CreatePaymentDocumentLinkDTO(
        document_type: 'vendor_bill',
        document_id: $vendorBill->id,
        amount_applied: Money::of(300, $this->company->currency->code)
    );

    $dto = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $payment->journal_id,
        currency_id: $payment->currency_id,
        payment_date: $payment->payment_date->toDateString(),
        // settlement inferred by presence of document links
        payment_type: PaymentType::Outbound,
        payment_method: PaymentMethod::BankTransfer,
        paid_to_from_partner_id: null,
        amount: null,
        document_links: [$linkDTO],
        reference: 'Test Settlement'
    );

    // Act
    $this->strategy->executeCreate($payment, $dto);

    // Assert
    expect($payment->paymentDocumentLinks)->toHaveCount(1);
    $link = $payment->paymentDocumentLinks->first();
    expect($link->vendor_bill_id)->toBe($vendorBill->id);
    expect($link->invoice_id)->toBeNull();
    expect($link->amount_applied->getAmount()->toFloat())->toBe(300.0);
});

test('it updates payment document links correctly', function () {
    // Arrange
    $invoice = Invoice::factory()->for($this->company)->create();
    $payment = Payment::factory()->for($this->company)->create([

        'payment_type' => PaymentType::Inbound,
        'currency_id' => $this->company->currency_id,
    ]);

    // Create existing link
    PaymentDocumentLink::factory()->create([
        'payment_id' => $payment->id,
        'company_id' => $this->company->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(200, $this->company->currency->code),
    ]);

    $newLinkDTO = new UpdatePaymentDocumentLinkDTO(
        document_type: 'invoice',
        document_id: $invoice->id,
        amount_applied: Money::of(400, $this->company->currency->code)
    );

    $dto = new UpdatePaymentDTO(
        payment: $payment,
        company_id: $this->company->id,
        journal_id: $payment->journal_id,
        currency_id: $payment->currency_id,
        payment_date: $payment->payment_date->toDateString(),
        // settlement inferred by presence of document links
        payment_type: PaymentType::Inbound,
        payment_method: PaymentMethod::BankTransfer,
        paid_to_from_partner_id: null,
        amount: null,
        document_links: [$newLinkDTO],
        reference: 'Updated Settlement',
        updated_by_user_id: $this->user->id
    );

    // Act
    $this->strategy->executeUpdate($payment, $dto);

    // Assert
    $payment->refresh();
    expect($payment->paymentDocumentLinks)->toHaveCount(1);
    $link = $payment->paymentDocumentLinks->first();
    expect($link->amount_applied->getAmount()->toFloat())->toBe(400.0);
});
