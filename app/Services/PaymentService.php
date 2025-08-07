<?php

namespace App\Services;

use App\Models\User;
use Brick\Money\Money;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\VendorBill;
use InvalidArgumentException;
use App\Events\PaymentConfirmed;
use Illuminate\Support\Facades\DB;
use App\Exceptions\UpdateNotAllowedException;
use App\Exceptions\DeletionNotAllowedException;
use App\Actions\Accounting\CreateJournalEntryForPaymentAction;

class PaymentService
{
    public function __construct(
        protected JournalEntryService $journalEntryService,
        protected CreateJournalEntryForPaymentAction $createJournalEntryForPaymentAction
    ) {
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
            $journalEntry = $this->createJournalEntryForPaymentAction->execute($payment, $user);

            $payment->journal_entry_id = $journalEntry->id;
            $payment->status = Payment::STATUS_CONFIRMED;
            $payment->save();

            // After confirming the payment, update the status of linked documents.
            $this->updateLinkedDocumentStatus($payment);

            PaymentConfirmed::dispatch($payment);

            return $payment;
        });
    }

    /**
     * Checks linked documents and updates their status to 'Paid' if fully paid.
     */
    protected function updateLinkedDocumentStatus(Payment $payment): void
    {
        $payment->load('paymentDocumentLinks.invoice', 'paymentDocumentLinks.vendorBill');

        foreach ($payment->paymentDocumentLinks as $link) {
            if ($link->invoice) {
                $invoice = $link->invoice;

                // --- START OF FIX ---
                // Correctly sum the 'amount_applied' from the pivot table for this invoice.
                $totalPaidMinor = $invoice->payments()
                                          ->where('payments.status', '!=', Payment::STATUS_CANCELED)
                                          ->sum('payment_document_links.amount_applied');

                // Convert the summed integer back to a Money object for comparison.
                $totalPaid = Money::ofMinor($totalPaidMinor, $invoice->currency->code);
                // --- END OF FIX ---

                if ($totalPaid->isGreaterThanOrEqualTo($invoice->total_amount)) {
                    $invoice->status = Invoice::STATUS_PAID;
                    $invoice->save();
                }
            }

            if ($link->vendorBill) {
                $vendorBill = $link->vendorBill;
                $totalPaidMinor = $vendorBill->payments()
                                             ->where('payments.status', '!=', Payment::STATUS_CANCELED)
                                             ->sum('payment_document_links.amount_applied');
                $totalPaid = Money::ofMinor($totalPaidMinor, $vendorBill->currency->code);

                if ($totalPaid->isGreaterThanOrEqualTo($vendorBill->total_amount)) {
                    $vendorBill->status = \App\Enums\Purchases\VendorBillStatus::Paid;
                    $vendorBill->save();
                }
            }
        }
    }

    /**
     * Cancels a confirmed payment by creating a reversing journal entry and a detailed audit log.
     */
    public function cancel(Payment $payment, User $user, string $reason): void // Add $reason parameter
    {
        if ($payment->status !== Payment::STATUS_CONFIRMED) {
            throw new \Exception('Only confirmed payments can be cancelled.');
        }

        DB::transaction(function () use ($payment, $user, $reason) {
            $originalEntry = $payment->journalEntry;
            if (!$originalEntry) {
                throw new \Exception('Cannot cancel payment without a journal entry.');
            }

            // Step 1: Create the explicit audit log with the reason.
            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'cancellation',
                'auditable_type' => get_class($payment),
                'auditable_id' => $payment->id,
                'description' => 'Payment Cancelled: ' . $reason,
                'old_values' => ['status' => $payment->status],
                'new_values' => ['status' => Payment::STATUS_CANCELED],
                'ip_address' => request()->ip(),
            ]);

            // Step 2: Create the reversal.
            $this->journalEntryService->createReversal(
                $originalEntry,
                'Cancellation of Payment #' . $payment->id . ': ' . $reason,
                $user
            );

            // Step 3: Update the payment's status.
            $payment->status = Payment::STATUS_CANCELED;
            $payment->save();
        });
    }

    /**
     * Deletes a payment, but only if it is in a draft state.
     * Enforces the accounting principle of immutability for confirmed transactions.
     *
     * @param Payment $payment The payment to be deleted.
     * @throws DeletionNotAllowedException If the payment is not in a draft state.
     */
    public function delete(Payment $payment): void
    {
        // THE GUARD CLAUSE: This is the core of the fix.
        if ($payment->status !== Payment::STATUS_DRAFT) {
            throw new DeletionNotAllowedException('Confirmed payments cannot be deleted. Please create a reversal entry instead.');
        }

        // If the payment is a draft, proceed with deletion.
        $payment->delete();
    }
}
