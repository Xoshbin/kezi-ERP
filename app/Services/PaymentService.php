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
use App\Models\Partner;
use App\Enums\Sales\InvoiceStatus;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Purchases\VendorBillStatus;
use InvalidArgumentException;
use App\Events\PaymentConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\UpdateNotAllowedException;
use App\Exceptions\DeletionNotAllowedException;
use App\Actions\Accounting\CreateJournalEntryForPaymentAction;
use App\Services\Payments\InterCompanyPaymentService;

class PaymentService
{
    public function __construct(
        protected JournalEntryService $journalEntryService,
        protected CreateJournalEntryForPaymentAction $createJournalEntryForPaymentAction,
        protected InvoiceService $invoiceService,
        protected VendorBillService $vendorBillService,
        protected InterCompanyPaymentService $interCompanyPaymentService
    ) {}

    /**
     * Confirm a draft payment, locking it and creating the journal entry.
     */
    public function confirm(Payment $payment, User $user): Payment
    {
        if ($payment->status !== PaymentStatus::Draft) {
            throw new UpdateNotAllowedException('Only draft payments can be confirmed.');
        }

        return DB::transaction(function () use ($payment, $user) {
            // Check if this is an inter-company payment and handle accordingly
            $this->handleInterCompanyPayment($payment, $user);

            // Create the corresponding journal entry.
            $journalEntry = $this->createJournalEntryForPaymentAction->execute($payment, $user);

            $payment->journal_entry_id = $journalEntry->id;
            $payment->status = PaymentStatus::Confirmed;
            $payment->save();

            // After confirming the payment, update the status of linked documents.
            $this->updateLinkedDocumentStatus($payment, $user);

            PaymentConfirmed::dispatch($payment);

            return $payment;
        });
    }

    /**
     * Checks linked documents and updates their status to 'Paid' if fully paid.
     * Ensures documents are properly posted before marking as paid.
     */
    protected function updateLinkedDocumentStatus(Payment $payment, User $user): void
    {
        $payment->load('paymentDocumentLinks.invoice', 'paymentDocumentLinks.vendorBill');

        foreach ($payment->paymentDocumentLinks as $link) {
            if ($link->invoice) {
                $invoice = $link->invoice;

                // Calculate total paid amount for this invoice
                $totalPaidMinor = $invoice->payments()
                    ->where('payments.status', '!=', PaymentStatus::Canceled)
                    ->sum('payment_document_links.amount_applied');

                $totalPaid = Money::ofMinor($totalPaidMinor, $invoice->currency->code);

                if ($totalPaid->isGreaterThanOrEqualTo($invoice->total_amount)) {
                    // If invoice is still draft, post it first to ensure all business logic is executed
                    if ($invoice->status === InvoiceStatus::Draft) {
                        $this->invoiceService->confirm($invoice, $user);
                        $invoice->refresh(); // Reload to get the updated status
                    }

                    // Only mark as paid if it's already posted
                    if ($invoice->status === InvoiceStatus::Posted) {
                        $invoice->status = InvoiceStatus::Paid;
                        $invoice->save();
                    }
                }
            }

            if ($link->vendorBill) {
                $vendorBill = $link->vendorBill;
                $totalPaidMinor = $vendorBill->payments()
                    ->where('payments.status', '!=', PaymentStatus::Canceled)
                    ->sum('payment_document_links.amount_applied');
                $totalPaid = Money::ofMinor($totalPaidMinor, $vendorBill->currency->code);

                if ($totalPaid->isGreaterThanOrEqualTo($vendorBill->total_amount)) {
                    // If vendor bill is still draft, post it first to ensure all business logic is executed
                    if ($vendorBill->status === VendorBillStatus::Draft) {
                        $this->vendorBillService->post($vendorBill, $user);
                        $vendorBill->refresh(); // Reload to get the updated status
                    }

                    // Only mark as paid if it's already posted
                    if ($vendorBill->status === VendorBillStatus::Posted) {
                        $vendorBill->status = VendorBillStatus::Paid;
                        $vendorBill->save();
                    }
                }
            }
        }
    }

    /**
     * Cancels a confirmed payment by creating a reversing journal entry and a detailed audit log.
     */
    public function cancel(Payment $payment, User $user, string $reason): void // Add $reason parameter
    {
        if ($payment->status !== PaymentStatus::Confirmed) {
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
                'new_values' => ['status' => PaymentStatus::Canceled],
                'ip_address' => request()->ip(),
            ]);

            // Step 2: Create the reversal.
            $this->journalEntryService->createReversal(
                $originalEntry,
                'Cancellation of Payment #' . $payment->id . ': ' . $reason,
                $user
            );

            // Step 3: Update the payment's status.
            $payment->status = PaymentStatus::Canceled;
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
        if ($payment->status !== PaymentStatus::Draft) {
            throw new DeletionNotAllowedException('Confirmed payments cannot be deleted. Please create a reversal entry instead.');
        }

        // If the payment is a draft, proceed with deletion.
        $payment->delete();
    }

    /**
     * Handle inter-company payment processing
     */
    protected function handleInterCompanyPayment(Payment $payment, User $user): void
    {
        $this->interCompanyPaymentService->processInterCompanyPayment($payment, $user);
    }

    /**
     * Check if a payment involves inter-company transactions
     */
    public function isInterCompanyPayment(Payment $payment): bool
    {
        return $this->interCompanyPaymentService->isInterCompanyPayment($payment);
    }
}
