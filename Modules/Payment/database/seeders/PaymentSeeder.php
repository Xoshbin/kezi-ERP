<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\PaymentDocumentLink;
use App\Models\VendorBill;
use Brick\Money\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        $journal = Journal::where('name->en', 'Bank (IQD)')->where('company_id', $company->id)->firstOrFail();

        // --- SCENARIO 1: Not Paid ---
        // Invoice INV-003 is intentionally left without any payment.

        // --- SCENARIO 2: Fully Paid (1 Payment) ---
        $billToFullyPay = \Modules\Purchase\Models\VendorBill::where('bill_reference', 'HC-INV-2025-002')->firstOrFail();
        $this->createPayment(
            company: $company,
            journal: $journal,
            partner: $billToFullyPay->vendor,
            paymentType: 'outbound',
            // FIXED: Convert the BigDecimal object to a string
            amount: (string) $billToFullyPay->total_amount->getAmount(),
            reference: 'Full payment for bill '.$billToFullyPay->bill_reference,
            vendorBill: $billToFullyPay
        );

        // --- SCENARIO 3: Partially Paid (1 Payment) ---
        $invoiceToPartiallyPay = \Modules\Sales\Models\Invoice::where('invoice_number', 'INV-001')->firstOrFail();
        $this->createPayment(
            company: $company,
            journal: $journal,
            partner: $invoiceToPartiallyPay->customer,
            paymentType: 'inbound',
            amount: 2000000, // This is a simple numeric value, so it's fine.
            reference: 'Partial payment for invoice '.$invoiceToPartiallyPay->invoice_number,
            invoice: $invoiceToPartiallyPay
        );

        // --- SCENARIO 4: Partially Paid (2 Payments) ---
        $billToPartiallyPayTwice = \Modules\Purchase\Models\VendorBill::where('bill_reference', 'PK-INV-2025-001')->firstOrFail();
        // First partial payment
        $this->createPayment(
            company: $company,
            journal: $journal,
            partner: $billToPartiallyPayTwice->vendor,
            paymentType: 'outbound',
            amount: 4000000,
            reference: 'Part 1 payment for bill '.$billToPartiallyPayTwice->bill_reference,
            vendorBill: $billToPartiallyPayTwice
        );
        // Second partial payment
        $this->createPayment(
            company: $company,
            journal: $journal,
            partner: $billToPartiallyPayTwice->vendor,
            paymentType: 'outbound',
            amount: 3500000,
            reference: 'Part 2 payment for bill '.$billToPartiallyPayTwice->bill_reference,
            vendorBill: $billToPartiallyPayTwice
        );

        // --- SCENARIO 5: Fully Paid (2 Payments) ---
        $invoiceToFullyPayTwice = \Modules\Sales\Models\Invoice::where('invoice_number', 'INV-002')->firstOrFail();
        // First payment
        $this->createPayment(
            company: $company,
            journal: $journal,
            partner: $invoiceToFullyPayTwice->customer,
            paymentType: 'inbound',
            amount: 1000000,
            reference: 'Part 1 payment for invoice '.$invoiceToFullyPayTwice->invoice_number,
            invoice: $invoiceToFullyPayTwice
        );
        // Second and final payment
        // FIXED: Use the minus() method for safe subtraction and then convert the result to a string.
        $firstPaymentAmount = Money::of(1000000, $company->currency->code);
        $remainingAmount = $invoiceToFullyPayTwice->total_amount->minus($firstPaymentAmount);

        $this->createPayment(
            company: $company,
            journal: $journal,
            partner: $invoiceToFullyPayTwice->customer,
            paymentType: 'inbound',
            amount: (string) $remainingAmount->getAmount(),
            reference: 'Final payment for invoice '.$invoiceToFullyPayTwice->invoice_number,
            invoice: $invoiceToFullyPayTwice
        );
    }

    /**
     * Helper function to create a payment and link it to a document.
     */
    private function createPayment($company, $journal, $partner, $paymentType, $amount, $reference, $invoice = null, $vendorBill = null): void
    {
        $payment = \Modules\Payment\Models\Payment::create([
            'company_id' => $company->id,
            'currency_id' => $company->currency_id,
            'payment_type' => $paymentType,
            'paid_to_from_partner_id' => $partner->id,
            'amount' => $amount,
            'payment_date' => Date::now(),
            'journal_id' => $journal->id,
            'status' => 'draft',
            'reference' => $reference,
        ]);

        $linkData = [
            'payment_id' => $payment->id,
            'amount_applied' => $amount,
        ];

        if ($invoice) {
            $linkData['invoice_id'] = $invoice->id;
        } elseif ($vendorBill) {
            $linkData['vendor_bill_id'] = $vendorBill->id;
        }

        PaymentDocumentLink::create($linkData);
    }
}
