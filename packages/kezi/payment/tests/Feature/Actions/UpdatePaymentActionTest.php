<?php

namespace Kezi\Payment\Tests\Feature\Actions;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\WithholdingTaxEntry;
use Kezi\Accounting\Models\WithholdingTaxType;
use Kezi\Foundation\Models\Partner;
use Kezi\Payment\Actions\Payments\UpdatePaymentAction;
use Kezi\Payment\DataTransferObjects\Payments\UpdatePaymentDocumentLinkDTO;
use Kezi\Payment\DataTransferObjects\Payments\UpdatePaymentDTO;
use Kezi\Payment\Enums\Payments\PaymentMethod;
use Kezi\Payment\Enums\Payments\PaymentStatus;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Payment\Models\Payment;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(UpdatePaymentAction::class);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id);
});

it('updates a draft payment and recalculates WHT for a vendor bill', function () {
    // Arrange
    $whtType = WithholdingTaxType::factory()->for($this->company)->create([
        'rate' => 0.05, // 5%
    ]);

    $vendor = Partner::factory()->for($this->company)->create([
        'withholding_tax_type_id' => $whtType->id,
    ]);

    $bill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendor->id,
        'total_amount' => 1000, // $1000
        'currency_id' => $this->company->currency_id,
        'status' => VendorBillStatus::Posted,
    ]);

    // Create initial payment (without WHT for proof of update)
    $payment = Payment::factory()->for($this->company)->create([
        'amount' => 1000,
        'status' => PaymentStatus::Draft,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $vendor->id,
    ]);

    $linkDTO = new UpdatePaymentDocumentLinkDTO(
        document_type: 'vendor_bill',
        document_id: $bill->id,
        amount_applied: Money::of(1000, $this->company->currency->code)
    );

    $dto = new UpdatePaymentDTO(
        payment: $payment,
        company_id: $this->company->id,
        journal_id: $this->company->default_bank_journal_id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        payment_type: PaymentType::Outbound,
        payment_method: PaymentMethod::Cash,
        paid_to_from_partner_id: $vendor->id,
        amount: null, // Will be calculated from links
        document_links: [$linkDTO],
        reference: 'Updated Payment',
        updated_by_user_id: $this->user->id
    );

    // Act
    $updatedPayment = $this->action->execute($dto);

    // Assert
    // Gross $1000, 5% Tax = $50. Net $950.
    expect($updatedPayment->amount->getAmount()->toFloat())->toBe(950.0);

    $whtEntry = WithholdingTaxEntry::where('payment_id', $updatedPayment->id)->first();
    expect($whtEntry)->not->toBeNull();
    expect($whtEntry->withheld_amount->getAmount()->toFloat())->toBe(50.0);
});

it('removes WHT if updated payment no longer applies to WHT vendor', function () {
    // Arrange
    $whtType = WithholdingTaxType::factory()->for($this->company)->create(['rate' => 0.05]);
    $vendorWithWht = Partner::factory()->for($this->company)->create(['withholding_tax_type_id' => $whtType->id]);
    $vendorWithoutWht = Partner::factory()->for($this->company)->create(['withholding_tax_type_id' => null]);

    $bill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendorWithWht->id,
        'total_amount' => 1000,
        'currency_id' => $this->company->currency_id,
        'status' => VendorBillStatus::Posted,
    ]);

    // Create payment with WHT entry already existing
    $payment = Payment::factory()->for($this->company)->create([
        'amount' => 950,
        'status' => PaymentStatus::Draft,
        'currency_id' => $this->company->currency_id,
        'paid_to_from_partner_id' => $vendorWithWht->id,
    ]);

    WithholdingTaxEntry::create([
        'company_id' => $this->company->id,
        'payment_id' => $payment->id,
        'withholding_tax_type_id' => $whtType->id,
        'vendor_id' => $vendorWithWht->id,
        'base_amount' => Money::of(1000, $this->company->currency->code),
        'withheld_amount' => Money::of(50, $this->company->currency->code),
        'rate_applied' => 0.05,
        'currency_id' => $this->company->currency_id,
    ]);

    // Update to a different vendor bill (no WHT)
    $otherBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendorWithoutWht->id,
        'total_amount' => 1000,
        'currency_id' => $this->company->currency_id,
        'status' => VendorBillStatus::Posted,
    ]);

    $linkDTO = new UpdatePaymentDocumentLinkDTO(
        document_type: 'vendor_bill',
        document_id: $otherBill->id,
        amount_applied: Money::of(1000, $this->company->currency->code)
    );

    $dto = new UpdatePaymentDTO(
        payment: $payment,
        company_id: $this->company->id,
        journal_id: $this->company->default_bank_journal_id,
        currency_id: $this->company->currency_id,
        payment_date: now()->toDateString(),
        payment_type: PaymentType::Outbound,
        payment_method: PaymentMethod::Cash,
        paid_to_from_partner_id: $vendorWithoutWht->id,
        amount: null, // Will be calculated from links
        document_links: [$linkDTO],
        reference: 'Updated to No WHT',
        updated_by_user_id: $this->user->id
    );

    // Act
    $updatedPayment = $this->action->execute($dto);

    // Assert
    expect($updatedPayment->amount->getAmount()->toFloat())->toBe(1000.0);
    expect(WithholdingTaxEntry::where('payment_id', $updatedPayment->id)->count())->toBe(0);
});
