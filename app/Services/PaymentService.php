<?php

namespace App\Services;

use App\Events\PaymentConfirmed;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorBill;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use App\Actions\Accounting\CreateJournalEntryForPaymentAction;

class PaymentService
{
    public function __construct(protected JournalEntryService $journalEntryService)
    {
    }

    /**
     * Create a new draft payment.
     */
    public function create(array $data, User $user): Payment
    {
        if (empty($data['currency_id'])) {
            if (empty($data['company_id'])) {
                throw new InvalidArgumentException('A company_id is required to create a payment without a currency.');
            }
            $company = Company::findOrFail($data['company_id']);
            $data['currency_id'] = $company->currency_id;
        }

        if (empty($data['paid_to_from_partner_id'])) {
            if (empty($data['documents'])) {
                throw new InvalidArgumentException('A partner_id or a document is required to create a payment.');
            }
            $document = $data['documents'][0];
            if ($document['document_type'] === 'invoice') {
                $invoice = Invoice::findOrFail($document['document_id']);
                $data['paid_to_from_partner_id'] = $invoice->customer_id;
            } elseif ($document['document_type'] === 'vendor_bill') {
                $vendorBill = VendorBill::findOrFail($document['document_id']);
                $data['paid_to_from_partner_id'] = $vendorBill->vendor_id;
            }
        }

        // Determine payment type from documents.
        if (empty($data['documents'])) {
            throw new InvalidArgumentException('At least one document is required to determine the payment type.');
        }

        $documentTypes = array_column($data['documents'], 'document_type');
        $hasInvoices = in_array('invoice', $documentTypes, true);
        $hasVendorBills = in_array('vendor_bill', $documentTypes, true);

        if ($hasInvoices && $hasVendorBills) {
            throw new InvalidArgumentException('A payment cannot be linked to both an invoice and a vendor bill simultaneously.');
        } elseif ($hasInvoices) {
            $data['payment_type'] = Payment::TYPE_INBOUND;
        } elseif ($hasVendorBills) {
            $data['payment_type'] = Payment::TYPE_OUTBOUND;
        } else {
            throw new InvalidArgumentException('Could not determine payment type from the provided documents.');
        }


        return DB::transaction(function () use ($data, $user) {
            // Calculate the total amount from the documents being paid.
            // MODIFIED: Use Money objects for precise summation.
            $currencyCode = Currency::find($data['currency_id'])->code;
            $totalAmount = Money::of(0, $currencyCode);
            foreach ($data['documents'] as $document) {
                $totalAmount = $totalAmount->plus(Money::of($document['amount'], $currencyCode));
            }
            $data['amount'] = $totalAmount;

            $payment = Payment::create($data + [
                'status' => Payment::STATUS_DRAFT,
                'created_by_user_id' => $user->id,
            ]);

            // Link the payment to the provided documents.
            foreach ($data['documents'] as $document) {
                $linkData = [
                    // MODIFIED: Ensure amount_applied is a Money object
                    'amount_applied' => Money::of($document['amount'], $currencyCode),
                ];

                if ($document['document_type'] === 'invoice') {
                    $linkData['invoice_id'] = $document['document_id'];
                } elseif ($document['document_type'] === 'vendor_bill') {
                    $linkData['vendor_bill_id'] = $document['document_id'];
                }

                $payment->paymentDocumentLinks()->create($linkData);
            }

            return $payment;
        });
    }

    /**
     * Confirm a draft payment, locking it and creating the journal entry.
     */
    public function confirm(Payment $payment, User $user): Payment
    {
        if ($payment->status !== Payment::STATUS_DRAFT) {
            throw new UpdateNotAllowedException('Only draft payments can be confirmed.');
        }

        return DB::transaction(function () use ($payment, $user) {
            // Create the corresponding journal entry.
            $journalEntry = (new CreateJournalEntryForPaymentAction())->execute($payment, $user);

            $payment->journal_entry_id = $journalEntry->id;
            $payment->status = Payment::STATUS_CONFIRMED;
            $payment->save();

            // MODIFIED: Update the status of the linked documents (invoices/vendor bills).
            $payment->load('paymentDocumentLinks.invoice', 'paymentDocumentLinks.vendorBill');

            foreach ($payment->paymentDocumentLinks as $link) {
                if ($link->invoice) {
                    // For now, we assume a full payment marks the invoice as paid.
                    // A more robust solution would sum all payments against the invoice total.
                    if ($payment->amount->isGreaterThanOrEqualTo($link->invoice->total_amount)) {
                        $link->invoice->status = Invoice::TYPE_PAID;
                        $link->invoice->save();
                    }
                }
                if ($link->vendorBill) {
                    // Same logic for vendor bills.
                    if ($payment->amount->isGreaterThanOrEqualTo($link->vendorBill->total_amount)) {
                        $link->vendorBill->status = VendorBill::TYPE_PAID;
                        $link->vendorBill->save();
                    }
                }
            }

            PaymentConfirmed::dispatch($payment);

            return $payment;
        });
    }

    /**
     * Update a draft payment. Confirmed payments are immutable.
     */
    public function update(Payment $payment, array $data): bool
    {
        // Guard Clause: Never allow updating a confirmed payment.
        if ($payment->status === Payment::STATUS_CONFIRMED) {
            throw new UpdateNotAllowedException('Cannot modify a confirmed payment.');
        }

        return $payment->update($data);
    }
}
