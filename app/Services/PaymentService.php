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
                        $link->invoice->status = Invoice::STATUS_PAID;
                        $link->invoice->save();
                    }
                }
                if ($link->vendorBill) {
                    // Same logic for vendor bills.
                    if ($payment->amount->isGreaterThanOrEqualTo($link->vendorBill->total_amount)) {
                        $link->vendorBill->status = VendorBill::STATUS_PAID;
                        $link->vendorBill->save();
                    }
                }
            }

            PaymentConfirmed::dispatch($payment);

            return $payment;
        });
    }
}
