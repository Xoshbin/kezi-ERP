<?php

namespace Modules\Sales\Enums\Sales;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Quote Status Enum
 *
 * Defines the lifecycle states of a quotation from draft through
 * conversion or rejection. Quotations are pre-commitment documents
 * that can be versioned and converted to Sales Orders or Invoices.
 */
enum QuoteStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';           // Initial draft being prepared
    case Sent = 'sent';             // Sent to customer, awaiting response
    case Accepted = 'accepted';     // Customer accepted the quote
    case Rejected = 'rejected';     // Customer rejected the quote
    case Expired = 'expired';       // Quote validity period has passed
    case Converted = 'converted';   // Converted to Sales Order or Invoice
    case Cancelled = 'cancelled';   // Cancelled by user

    /**
     * Get the human-readable label for the status.
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => __('sales::quote.statuses.draft'),
            self::Sent => __('sales::quote.statuses.sent'),
            self::Accepted => __('sales::quote.statuses.accepted'),
            self::Rejected => __('sales::quote.statuses.rejected'),
            self::Expired => __('sales::quote.statuses.expired'),
            self::Converted => __('sales::quote.statuses.converted'),
            self::Cancelled => __('sales::quote.statuses.cancelled'),
        };
    }

    /**
     * Get the color associated with the status for UI display.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::Expired => 'warning',
            self::Converted => 'primary',
            self::Cancelled => 'gray',
        };
    }

    /**
     * Check if the quote can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this, [self::Draft, self::Sent]);
    }

    /**
     * Check if the quote can be sent to customer.
     */
    public function canBeSent(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if the quote can be accepted.
     */
    public function canBeAccepted(): bool
    {
        return $this === self::Sent;
    }

    /**
     * Check if the quote can be rejected.
     */
    public function canBeRejected(): bool
    {
        return $this === self::Sent;
    }

    /**
     * Check if the quote can be converted to Sales Order or Invoice.
     */
    public function canBeConverted(): bool
    {
        return $this === self::Accepted;
    }

    /**
     * Check if a new revision can be created from this quote.
     */
    public function canCreateRevision(): bool
    {
        return in_array($this, [self::Sent, self::Rejected]);
    }

    /**
     * Check if the quote can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return ! in_array($this, [self::Converted, self::Cancelled]);
    }

    /**
     * Check if this status represents a final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Converted, self::Cancelled, self::Expired]);
    }

    /**
     * Get all statuses that represent active quotes.
     *
     * @return array<self>
     */
    public static function activeStatuses(): array
    {
        return [
            self::Draft,
            self::Sent,
            self::Accepted,
        ];
    }

    /**
     * Get all statuses that represent closed quotes.
     *
     * @return array<self>
     */
    public static function closedStatuses(): array
    {
        return [
            self::Rejected,
            self::Expired,
            self::Converted,
            self::Cancelled,
        ];
    }
}
