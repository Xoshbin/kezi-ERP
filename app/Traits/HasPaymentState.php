<?php

namespace App\Traits;

use Exception;
use App\Enums\Payments\PaymentStatus;
use Brick\Money\Money;
use App\Enums\Shared\PaymentState;
use App\Services\CurrencyConverterService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;

/**
 * Trait HasPaymentState
 *
 * Provides payment state calculation functionality for financial documents
 * that can receive payments (invoices, vendor bills, etc.).
 *
 * This trait assumes the model has:
 * - payments() BelongsToMany relationship through payment_document_links
 * - currency() BelongsTo relationship
 * - total_amount Money field
 *
 * The payment state is calculated dynamically based on the sum of applied payments
 * compared to the total amount of the document. This follows accounting best practices
 * by separating workflow state from payment state and ensuring data consistency.
 */
trait HasPaymentState
{
    /**
     * Computes the payment state of the document on the fly.
     * This follows the Odoo pattern of separating workflow state from payment state.
     * The value is not stored in the database to maintain data consistency.
     *
     * @return Attribute
     */
    protected function paymentState(): Attribute
    {
        return Attribute::make(
            get: function (): PaymentState {
                $paidAmount = $this->getPaidAmount();

                if ($paidAmount->isZero()) {
                    return PaymentState::NotPaid;
                }

                // isGreaterThanOrEqual() handles cases of overpayment correctly.
                if ($paidAmount->isGreaterThanOrEqualTo($this->total_amount)) {
                    return PaymentState::Paid;
                }

                return PaymentState::PartiallyPaid;
            }
        );
    }

    /**
     * Get the total amount paid for this document.
     * This is a helper method that returns the actual Money amount paid.
     * Only considers payments with confirmed or reconciled status.
     * Handles multi-currency payments by converting all payment amounts to document currency.
     *
     * @return Money
     */
    public function getPaidAmount(): Money
    {
        // Load the company and currency converter service
        $this->load(['company', 'currency']);
        $currencyConverter = app(CurrencyConverterService::class);

        // Get all confirmed/reconciled payment document links for this document
        $paymentLinks = $this->paymentDocumentLinks()
            ->whereHas('payment', function ($query) {
                $query->whereIn('status', [PaymentStatus::Confirmed, PaymentStatus::Reconciled]);
            })
            ->with(['payment.currency'])
            ->get();

        $totalPaidInDocumentCurrency = Money::of(0, $this->currency->code);

        foreach ($paymentLinks as $link) {
            $payment = $link->payment;
            $amountApplied = $link->amount_applied; // This is in payment currency

            // If payment currency is different from document currency, convert it
            if ($payment->currency_id !== $this->currency_id) {
                try {
                    // Convert from payment currency to document currency
                    $convertedAmount = $currencyConverter->convert(
                        $amountApplied,
                        $this->currency,
                        $payment->payment_date,
                        $this->company
                    );
                    $totalPaidInDocumentCurrency = $totalPaidInDocumentCurrency->plus($convertedAmount);
                } catch (Exception $e) {
                    // If conversion fails, log and skip this payment
                    Log::warning("Failed to convert payment amount for payment {$payment->id}: " . $e->getMessage());
                    continue;
                }
            } else {
                // Same currency, add directly
                $totalPaidInDocumentCurrency = $totalPaidInDocumentCurrency->plus($amountApplied);
            }
        }

        return $totalPaidInDocumentCurrency;
    }

    /**
     * Get the remaining amount to be paid for this document.
     *
     * @return Money
     */
    public function getRemainingAmount(): Money
    {
        $paidAmount = $this->getPaidAmount();
        $remaining = $this->total_amount->minus($paidAmount);

        // Ensure we don't return negative amounts (in case of overpayment)
        return $remaining->isNegative() ? Money::of(0, $this->currency->code) : $remaining;
    }

    /**
     * Check if the document is fully paid.
     *
     * @return bool
     */
    public function isFullyPaid(): bool
    {
        return $this->paymentState === PaymentState::Paid;
    }

    /**
     * Check if the document is partially paid.
     *
     * @return bool
     */
    public function isPartiallyPaid(): bool
    {
        return $this->paymentState === PaymentState::PartiallyPaid;
    }

    /**
     * Check if the document is not paid at all.
     *
     * @return bool
     */
    public function isNotPaid(): bool
    {
        return $this->paymentState === PaymentState::NotPaid;
    }
}
