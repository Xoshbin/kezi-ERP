<?php

namespace Modules\Payment\Tests\Feature\Actions;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\WithholdingTaxEntry;
use Modules\Accounting\Models\WithholdingTaxType;
use Modules\Foundation\Models\Partner;
use Modules\Payment\Actions\Payments\CreatePaymentAction;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreatePaymentAction::class);
});

it('creates a draft payment with withholding tax for a vendor bill', function () {
    // Arrange
    $whtType = WithholdingTaxType::factory()->for($this->company)->create([
        'rate' => 0.05, // 5%
    ]);

    $vendor = Partner::factory()->for($this->company)->create([
        'withholding_tax_type_id' => $whtType->id,
    ]);

    $journal = Journal::factory()->for($this->company)->create([
        'type' => \Modules\Accounting\Enums\Accounting\JournalType::Bank,
    ]);

    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendor->id,
        'currency_id' => $this->company->currency_id,
        'total_amount' => Money::of(1000, $this->company->currency->code),
        'status' => 'posted',
    ]);

    $link = new CreatePaymentDocumentLinkDTO(
        document_type: 'vendor_bill',
        document_id: $vendorBill->id,
        amount_applied: Money::of(1000, $this->company->currency->code) // Gross amount applied
    );

    $dto = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $journal->id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        payment_type: PaymentType::Outbound,
        payment_method: \Modules\Payment\Enums\Payments\PaymentMethod::BankTransfer,
        paid_to_from_partner_id: null,
        amount: null,
        document_links: [$link],
        reference: 'WHT Test Payment'
    );

    // Act
    $payment = $this->action->execute($dto, $this->user);

    // Assert
    expect($payment)->toBeInstanceOf(Payment::class);
    expect($payment->status)->toBe(PaymentStatus::Draft);

    // Check WHT: Gross 1000, 5% Tax = 50. Net payment = 950.
    expect($payment->amount->getAmount()->toFloat())->toBe(950.0);

    $whtEntry = WithholdingTaxEntry::where('payment_id', $payment->id)->first();
    expect($whtEntry)->not->toBeNull();
    expect($whtEntry->withheld_amount->getAmount()->toFloat())->toBe(50.0);
    expect($whtEntry->base_amount->getAmount()->toFloat())->toBe(1000.0);
    expect($whtEntry->withholding_tax_type_id)->toBe($whtType->id);
});

it('throws exception if payment is linked to both invoices and vendor bills', function () {
    // Arrange
    $invoice = \Modules\Sales\Models\Invoice::factory()->for($this->company)->create();
    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($this->company)->create();

    $links = [
        new CreatePaymentDocumentLinkDTO('invoice', $invoice->id, Money::of(100, $this->company->currency->code)),
        new CreatePaymentDocumentLinkDTO('vendor_bill', $vendorBill->id, Money::of(100, $this->company->currency->code)),
    ];

    $dto = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: Journal::factory()->create()->id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        payment_type: PaymentType::Inbound,
        payment_method: \Modules\Payment\Enums\Payments\PaymentMethod::Cash,
        paid_to_from_partner_id: null,
        amount: null,
        document_links: $links,
        reference: 'Invalid Mixed Payment'
    );

    // Act & Assert
    expect(fn () => $this->action->execute($dto, $this->user))
        ->toThrow(InvalidArgumentException::class, 'A payment cannot be linked to both invoices and vendor bills simultaneously.');
});

it('throws exception for settlement payment without document links', function () {
    // Note: The logic in CreatePaymentAction has a bug/weirdness:
    // $isSettlement = ! empty($dto->document_links);
    // if ($isSettlement === true && empty($dto->document_links)) { ... }
    // This second check is impossible to trigger if $isSettlement is truly (! empty).

    // However, there is another check:
    // if ($isSettlement === false && empty($dto->paid_to_from_partner_id)) {
    //     throw new InvalidArgumentException('Payments without document links must specify a partner.');
    // }

    $dto = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: Journal::factory()->create()->id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        payment_type: PaymentType::Inbound,
        payment_method: \Modules\Payment\Enums\Payments\PaymentMethod::Cash,
        paid_to_from_partner_id: null,
        amount: Money::of(100, $this->company->currency->code),
        document_links: [],
        reference: 'Standalone without partner'
    );

    expect(fn () => $this->action->execute($dto, $this->user))
        ->toThrow(InvalidArgumentException::class, 'Payments without document links must specify a partner.');
});

it('correctly converts WHT amount to base currency in multi-currency payment', function () {
    // Arrange
    $usdCurrency = \Modules\Foundation\Models\Currency::factory()->create([
        'code' => 'USD',
        'decimal_places' => 2,
    ]);

    // Set exchange rate: 1 USD = 1500 IQD (Base)
    \Modules\Foundation\Models\CurrencyRate::factory()->for($this->company)->create([
        'currency_id' => $usdCurrency->id,
        'rate' => 1500,
        'effective_date' => now()->toDateString(),
    ]);

    $whtType = WithholdingTaxType::factory()->for($this->company)->create([
        'rate' => 0.1, // 10%
    ]);

    $vendor = Partner::factory()->for($this->company)->create([
        'withholding_tax_type_id' => $whtType->id,
    ]);

    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendor->id,
        'currency_id' => $usdCurrency->id,
        'total_amount' => Money::of(100, 'USD'),
        'status' => 'posted',
    ]);

    $link = new CreatePaymentDocumentLinkDTO(
        document_type: 'vendor_bill',
        document_id: $vendorBill->id,
        amount_applied: Money::of(100, 'USD')
    );

    $journal = Journal::factory()->for($this->company)->create([
        'type' => \Modules\Accounting\Enums\Accounting\JournalType::Bank,
    ]);

    $dto = new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $journal->id,
        currency_id: $usdCurrency->id,
        payment_date: now()->toDateString(),
        payment_type: PaymentType::Outbound,
        payment_method: \Modules\Payment\Enums\Payments\PaymentMethod::BankTransfer,
        paid_to_from_partner_id: null,
        amount: null,
        document_links: [$link],
        reference: 'Multi-currency WHT Test'
    );

    // Act
    $payment = $this->action->execute($dto, $this->user);

    // Assert
    // Gross $100, 10% Tax = $10. Net $90.
    expect($payment->amount->getAmount()->toFloat())->toBe(90.0);
    expect($payment->amount->getCurrency()->getCurrencyCode())->toBe('USD');

    $whtEntry = WithholdingTaxEntry::where('payment_id', $payment->id)->first();
    expect($whtEntry)->not->toBeNull();

    // base_amount should be Gross in Base Currency: $100 * 1500 = 150,000 IQD
    expect($whtEntry->base_amount->getAmount()->toFloat())->toBe(150000.0);
    // withheld_amount should be Tax in Base Currency: $10 * 1500 = 15,000 IQD
    expect($whtEntry->withheld_amount->getAmount()->toFloat())->toBe(15000.0);
    expect($whtEntry->currency_id)->toBe($usdCurrency->id); // Original currency stored
});
