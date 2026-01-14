<?php

namespace Modules\Purchase\Enums\Purchases;

/**
 * ThreeWayMatchStatus Enum
 *
 * Represents the matching status between Purchase Order, Goods Receipt (GRN), and Vendor Bill.
 * Three-way matching is a standard procurement control that ensures:
 * 1. PO was created and approved
 * 2. Goods were received (GRN validated)
 * 3. Bill matches what was ordered and received
 */
enum ThreeWayMatchStatus: string
{
    case NotApplicable = 'not_applicable';        // No PO linked (standalone bill)
    case PendingReceipt = 'pending_receipt';      // PO confirmed, GRN not yet validated
    case PartiallyReceived = 'partially_received'; // Some goods received, more expected
    case FullyMatched = 'fully_matched';          // PO + GRN + Bill quantities match
    case QuantityMismatch = 'quantity_mismatch';  // Bill quantity differs from received
    case PriceMismatch = 'price_mismatch';        // Bill price differs from PO price

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::NotApplicable => __('purchase::three_way_matching.status.not_applicable'),
            self::PendingReceipt => __('purchase::three_way_matching.status.pending_receipt'),
            self::PartiallyReceived => __('purchase::three_way_matching.status.partially_received'),
            self::FullyMatched => __('purchase::three_way_matching.status.fully_matched'),
            self::QuantityMismatch => __('purchase::three_way_matching.status.quantity_mismatch'),
            self::PriceMismatch => __('purchase::three_way_matching.status.price_mismatch'),
        };
    }

    /**
     * Get the color for UI display (Filament badge).
     */
    public function color(): string
    {
        return match ($this) {
            self::NotApplicable => 'gray',
            self::PendingReceipt => 'warning',
            self::PartiallyReceived => 'info',
            self::FullyMatched => 'success',
            self::QuantityMismatch => 'danger',
            self::PriceMismatch => 'danger',
        };
    }

    /**
     * Check if this status blocks bill posting.
     */
    public function blocksPosting(): bool
    {
        return $this === self::PendingReceipt;
    }

    /**
     * Check if this status indicates a problem.
     */
    public function hasMismatch(): bool
    {
        return in_array($this, [self::QuantityMismatch, self::PriceMismatch], true);
    }
}
