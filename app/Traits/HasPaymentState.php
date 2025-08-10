<?php

namespace App\Traits;

use App\Enums\Payments\PaymentStatus;
use Brick\Money\Money;
use App\Enums\Shared\PaymentState;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
                // Only consider payments that have actual financial impact (Confirmed or Reconciled)
                // Draft and Canceled payments are accounting non-events and should be ignored
                $paidAmountMinor = $this->payments()
                    ->whereIn('status', [PaymentStatus::Confirmed, PaymentStatus::Reconciled])
                    ->sum('payment_document_links.amount_applied');

                // The sum is returned as an integer (minor units). We convert it to a Money object.
                $paidAmount = Money::ofMinor($paidAmountMinor, $this->currency->code);

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
     *
     * @return Money
     */
    public function getPaidAmount(): Money
    {
        $paidAmountMinor = $this->payments()
            ->whereIn('status', [PaymentStatus::Confirmed, PaymentStatus::Reconciled])
            ->sum('payment_document_links.amount_applied');

        return Money::ofMinor($paidAmountMinor, $this->currency->code);
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
