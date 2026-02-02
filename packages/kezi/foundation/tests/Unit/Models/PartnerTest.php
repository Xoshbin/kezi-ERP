<?php

namespace Kezi\Foundation\Tests\Unit\Models;

use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Kezi\Foundation\Enums\Partners\PartnerType;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Payment\Models\Payment;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->setupWithConfiguredCompany();
    $this->currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->createSafely(['code' => 'USD']);
    $this->company->update(['currency_id' => $this->currency->id]);
});

describe('Partner Financial Methods', function () {

    it('calculates customer outstanding balance correctly', function () {
        $partner = Partner::factory()->for($this->company)->create([
            'type' => PartnerType::Customer,
        ]);

        // 1. Posted Invoice - Full amount outstanding (1000)
        Invoice::factory()->for($this->company)->create([
            'customer_id' => $partner->id,
            'status' => InvoiceStatus::Posted,
            'total_amount' => Money::of(1000, 'USD'),
            'currency_id' => $this->currency->id,
        ]);

        // 2. Draft Invoice - Should be ignored
        Invoice::factory()->for($this->company)->create([
            'customer_id' => $partner->id,
            'status' => InvoiceStatus::Draft,
            'total_amount' => Money::of(500, 'USD'),
            'currency_id' => $this->currency->id,
        ]);

        // 3. Partially Paid Invoice (1500 total, 500 paid, 1000 remaining)
        $partiallyPaidInvoice = Invoice::factory()->for($this->company)->create([
            'customer_id' => $partner->id,
            'status' => InvoiceStatus::Posted,
            'total_amount' => Money::of(1500, 'USD'),
            'currency_id' => $this->currency->id,
        ]);

        $payment = Payment::factory()->for($this->company)->create([
            'paid_to_from_partner_id' => $partner->id,
            'status' => \Kezi\Payment\Enums\Payments\PaymentStatus::Confirmed,
            'amount' => Money::of(500, 'USD'),
            'currency_id' => $this->currency->id,
        ]);

        \Kezi\Payment\Models\PaymentDocumentLink::factory()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $partiallyPaidInvoice->id,
            'amount_applied' => Money::of(500, 'USD'),
            'company_id' => $this->company->id,
        ]);

        // 4. Paid Invoice (2000 total, 2000 paid, 0 remaining)
        $paidInvoice = Invoice::factory()->for($this->company)->create([
            'customer_id' => $partner->id,
            'status' => InvoiceStatus::Paid,
            'total_amount' => Money::of(2000, 'USD'),
            'currency_id' => $this->currency->id,
        ]);

        $fullPayment = Payment::factory()->for($this->company)->create([
            'paid_to_from_partner_id' => $partner->id,
            'status' => \Kezi\Payment\Enums\Payments\PaymentStatus::Confirmed,
            'amount' => Money::of(2000, 'USD'),
            'currency_id' => $this->currency->id,
        ]);

        \Kezi\Payment\Models\PaymentDocumentLink::factory()->create([
            'payment_id' => $fullPayment->id,
            'invoice_id' => $paidInvoice->id,
            'amount_applied' => Money::of(2000, 'USD'),
            'company_id' => $this->company->id,
        ]);

        // Verify counts
        expect($partner->invoices()->count())->toBe(4);
        expect($partner->invoices()->whereIn('status', [InvoiceStatus::Posted, InvoiceStatus::Paid])->count())->toBe(3);

        // Debug help: check individual remaining amounts if it fails again
        // Invoice 1: 1000
        // Invoice 3: 1500 - 500 = 1000
        // Invoice 4: 2000 - 2000 = 0
        // Total Expected: 2000

        $balance = $partner->getCustomerOutstandingBalance();
        expect($balance->getAmount()->toInt())->toBe(2000);
    });

    it('calculates vendor outstanding balance correctly', function () {
        $partner = Partner::factory()->for($this->company)->create([
            'type' => PartnerType::Vendor,
        ]);

        // 1. Posted Vendor Bill - Full amount outstanding (1500)
        VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $partner->id,
            'status' => VendorBillStatus::Posted,
            'total_amount' => Money::of(1500, 'USD'),
            'currency_id' => $this->currency->id,
        ]);

        // 2. Partially Paid Bill (1000 total, 400 paid, 600 remaining)
        $partialBill = VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $partner->id,
            'status' => VendorBillStatus::Posted,
            'total_amount' => Money::of(1000, 'USD'),
            'currency_id' => $this->currency->id,
        ]);

        $payment = Payment::factory()->for($this->company)->create([
            'paid_to_from_partner_id' => $partner->id,
            'status' => \Kezi\Payment\Enums\Payments\PaymentStatus::Confirmed,
            'amount' => Money::of(400, 'USD'),
            'currency_id' => $this->currency->id,
        ]);

        \Kezi\Payment\Models\PaymentDocumentLink::factory()->create([
            'payment_id' => $payment->id,
            'vendor_bill_id' => $partialBill->id,
            'amount_applied' => Money::of(400, 'USD'),
            'company_id' => $this->company->id,
        ]);

        // Total Expected: 1500 + 600 = 2100
        $balance = $partner->getVendorOutstandingBalance();
        expect($balance->getAmount()->toInt())->toBe(2100);
    });

    it('calculates customer overdue balance correctly', function () {
        $partner = Partner::factory()->for($this->company)->create([
            'type' => PartnerType::Customer,
        ]);

        // Overdue Invoice
        Invoice::factory()->for($this->company)->create([
            'customer_id' => $partner->id,
            'status' => InvoiceStatus::Posted,
            'total_amount' => 800,
            'due_date' => Carbon::yesterday(),
            'currency_id' => $this->currency->id,
        ]);

        // Not Overdue Invoice
        Invoice::factory()->for($this->company)->create([
            'customer_id' => $partner->id,
            'status' => InvoiceStatus::Posted,
            'total_amount' => 400,
            'due_date' => Carbon::tomorrow(),
            'currency_id' => $this->currency->id,
        ]);

        $balance = $partner->getCustomerOverdueBalance();
        expect($balance->getAmount()->toInt())->toBe(800);
    });

    it('calculates vendor overdue balance correctly', function () {
        $partner = Partner::factory()->for($this->company)->create([
            'type' => PartnerType::Vendor,
        ]);

        // Overdue Bill
        VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $partner->id,
            'status' => VendorBillStatus::Posted,
            'total_amount' => 1200,
            'due_date' => Carbon::yesterday(),
            'currency_id' => $this->currency->id,
        ]);

        // Not Overdue Bill
        VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $partner->id,
            'status' => VendorBillStatus::Posted,
            'total_amount' => 600,
            'due_date' => Carbon::tomorrow(),
            'currency_id' => $this->currency->id,
        ]);

        $balance = $partner->getVendorOverdueBalance();
        expect($balance->getAmount()->toInt())->toBe(1200);
    });

    it('returns zero for incorrect partner types', function () {
        $partner = Partner::factory()->for($this->company)->create([
            'type' => PartnerType::Customer,
        ]);

        expect($partner->getVendorOutstandingBalance()->isZero())->toBeTrue();

        $partner->type = PartnerType::Vendor;
        expect($partner->getCustomerOutstandingBalance()->isZero())->toBeTrue();
    });

    it('calculates last transaction date correctly', function () {
        $partner = Partner::factory()->for($this->company)->create();

        $date1 = Carbon::now()->subDays(10);
        $date2 = Carbon::now()->subDays(5);
        $date3 = Carbon::now()->subDays(2);

        Invoice::factory()->for($this->company)->create([
            'customer_id' => $partner->id,
            'invoice_date' => $date1,
        ]);

        VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $partner->id,
            'bill_date' => $date3,
        ]);

        Payment::factory()->for($this->company)->create([
            'paid_to_from_partner_id' => $partner->id,
            'payment_date' => $date2,
        ]);

        expect($partner->getLastTransactionDate()->toDateString())->toBe($date3->toDateString());
    });

    it('identifies partners with overdue amounts', function () {
        $partner = Partner::factory()->for($this->company)->create([
            'type' => PartnerType::Customer,
        ]);

        expect($partner->hasOverdueAmounts())->toBeFalse();

        Invoice::factory()->for($this->company)->create([
            'customer_id' => $partner->id,
            'status' => InvoiceStatus::Posted,
            'total_amount' => 100,
            'due_date' => Carbon::yesterday(),
            'currency_id' => $this->currency->id,
        ]);

        expect($partner->hasOverdueAmounts())->toBeTrue();
    });

    it('calculates total lifetime value correctly', function () {
        $partner = Partner::factory()->for($this->company)->create([
            'type' => PartnerType::Both,
        ]);

        Invoice::factory()->for($this->company)->create([
            'customer_id' => $partner->id,
            'status' => InvoiceStatus::Posted,
            'total_amount' => 1000,
            'currency_id' => $this->currency->id,
        ]);

        VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $partner->id,
            'status' => VendorBillStatus::Posted,
            'total_amount' => 500,
            'currency_id' => $this->currency->id,
        ]);

        expect($partner->getTotalLifetimeValue()->getAmount()->toInt())->toBe(1500);
    });
});

describe('Partner Default Tax', function () {

    it('can have a default tax relationship', function () {
        $tax = \Kezi\Accounting\Models\Tax::factory()->for($this->company)->create([
            'name' => 'VAT 15%',
            'rate' => 0.15,
            'is_active' => true,
        ]);

        $partner = Partner::factory()->for($this->company)->create([
            'default_tax_id' => $tax->id,
        ]);

        expect($partner->defaultTax)->not->toBeNull()
            ->and($partner->defaultTax->id)->toBe($tax->id)
            ->and($partner->defaultTax->name)->toBe('VAT 15%')
            ->and($partner->defaultTax->rate)->toBe(0.15);
    });

    it('returns null when default tax is not set', function () {
        $partner = Partner::factory()->for($this->company)->create([
            'default_tax_id' => null,
        ]);

        expect($partner->defaultTax)->toBeNull();
    });
});
