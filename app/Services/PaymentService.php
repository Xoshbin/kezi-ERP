<?php

namespace App\Services;

use Exception;
use App\Models\User;
use Brick\Money\Money;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\VendorBill;
use App\Models\PaymentDocumentLink;
use App\Enums\Sales\InvoiceStatus;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Purchases\VendorBillStatus;
use InvalidArgumentException;
use App\Events\PaymentConfirmed;
use Illuminate\Support\Facades\DB;
use App\Exceptions\UpdateNotAllowedException;
use App\Exceptions\DeletionNotAllowedException;
use App\Actions\Accounting\CreateJournalEntryForPaymentAction;
use App\Services\CurrencyConverterService;
use App\Services\ExchangeGainLossService;

class PaymentService
{
    public function __construct(
        protected JournalEntryService $journalEntryService,
        protected CreateJournalEntryForPaymentAction $createJournalEntryForPaymentAction,
        protected InvoiceService $invoiceService,
        protected VendorBillService $vendorBillService,
        protected CurrencyConverterService $currencyConverter,
        protected ExchangeGainLossService $exchangeGainLossService
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
            // Process multi-currency amounts before confirming
            $this->processMultiCurrencyPayment($payment);

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
            throw new Exception('Only confirmed payments can be cancelled.');
        }

        DB::transaction(function () use ($payment, $user, $reason) {
            $originalEntry = $payment->journalEntry;
            if (!$originalEntry) {
                throw new Exception('Cannot cancel payment without a journal entry.');
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
     * Process multi-currency amounts for a payment.
     * Captures exchange rate and converts amounts to company base currency.
     */
    protected function processMultiCurrencyPayment(Payment $payment): void
    {
        // Load necessary relationships
        $payment->load(['company', 'currency']);

        // If payment is in company base currency, set rate to 1.0
        if ($payment->currency_id === $payment->company->currency_id) {
            $payment->exchange_rate_at_payment = 1.0;
            $payment->amount_company_currency = $payment->amount;
            return;
        }

        // Get exchange rate for the payment date
        $exchangeRate = $this->currencyConverter->getExchangeRate($payment->currency, $payment->payment_date);

        // If no exchange rate is found, skip multi-currency processing for backward compatibility
        if (!$exchangeRate) {
            // For backward compatibility, just set the rate to 1.0 and use original amount
            $payment->exchange_rate_at_payment = 1.0;
            $payment->amount_company_currency = $payment->amount;
            return;
        }

        // Convert amount to company currency
        $companyCurrency = $payment->company->currency;

        $amountCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $payment->amount,
            $payment->currency,
            $companyCurrency,
            $payment->payment_date
        );

        // Update payment with converted amounts
        $payment->update([
            'exchange_rate_at_payment' => $exchangeRate,
            'amount_company_currency' => $amountCompanyCurrency,
        ]);
    }

    /**
     * Apply a payment to documents with exchange gain/loss calculation.
     * This method handles payment application with multi-currency support.
     */
    public function applyToDocuments(Payment $payment, array $applications, User $user): array
    {
        if ($payment->status !== PaymentStatus::Confirmed) {
            throw new Exception('Only confirmed payments can be applied to documents');
        }

        $links = [];

        return DB::transaction(function () use ($payment, $applications, &$links) {
            foreach ($applications as $application) {
                $document = $this->getDocument($application['document_type'], $application['document_id']);
                $amountApplied = $application['amount'];

                // Create payment document link
                $link = $this->createPaymentDocumentLink($payment, $document, $amountApplied);
                $links[] = $link;

                // Calculate and post exchange gain/loss if applicable
                $this->exchangeGainLossService->processRealizedGainLoss(
                    $payment,
                    $document,
                    $amountApplied
                );
            }

            return $links;
        });
    }

    /**
     * Get document by type and ID.
     */
    protected function getDocument(string $documentType, int $documentId)
    {
        return match ($documentType) {
            'invoice' => Invoice::findOrFail($documentId),
            'vendor_bill' => VendorBill::findOrFail($documentId),
            default => throw new InvalidArgumentException("Invalid document type: {$documentType}")
        };
    }

    /**
     * Create payment document link.
     */
    protected function createPaymentDocumentLink(Payment $payment, $document, Money $amountApplied)
    {
        $linkData = [
            'payment_id' => $payment->id,
            'amount_applied' => $amountApplied,
        ];

        if ($document instanceof Invoice) {
            $linkData['invoice_id'] = $document->id;
        } elseif ($document instanceof VendorBill) {
            $linkData['vendor_bill_id'] = $document->id;
        }

        return PaymentDocumentLink::create($linkData);
    }
}
