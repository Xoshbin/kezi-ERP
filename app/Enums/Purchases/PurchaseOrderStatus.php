<?php

namespace App\Enums\Purchases;

/**
 * Purchase Order Status Enum
 *
 * Defines the complete lifecycle states of a purchase order from RFQ through final closure.
 * This enum implements the comprehensive procurement workflow with proper status tracking
 * for quotations, commitments, receiving, billing, and completion phases.
 */
enum PurchaseOrderStatus: string
{
    // Pre-commitment phase
    case RFQ = 'rfq';                           // Request for Quotation
    case RFQSent = 'rfq_sent';                  // RFQ sent to vendor(s)

        // Commitment phase
    case Draft = 'draft';                       // PO being prepared
    case Sent = 'sent';                         // PO sent to vendor
    case Confirmed = 'confirmed';               // PO confirmed by vendor

        // Fulfillment phase
    case ToReceive = 'to_receive';              // Waiting for delivery
    case PartiallyReceived = 'partially_received';
    case FullyReceived = 'fully_received';

        // Billing phase
    case ToBill = 'to_bill';                    // Goods received, waiting for bill
    case PartiallyBilled = 'partially_billed';
    case FullyBilled = 'fully_billed';

        // Final states
    case Done = 'done';                         // Complete and closed
    case Cancelled = 'cancelled';

    /**
     * Get the human-readable label for the purchase order status.
     */
    public function label(): string
    {
        return match ($this) {
            // Pre-commitment phase
            self::RFQ => __('purchase_orders.status.rfq'),
            self::RFQSent => __('purchase_orders.status.rfq_sent'),

            // Commitment phase
            self::Draft => __('purchase_orders.status.draft'),
            self::Sent => __('purchase_orders.status.sent'),
            self::Confirmed => __('purchase_orders.status.confirmed'),

            // Fulfillment phase
            self::ToReceive => __('purchase_orders.status.to_receive'),
            self::PartiallyReceived => __('purchase_orders.status.partially_received'),
            self::FullyReceived => __('purchase_orders.status.fully_received'),

            // Billing phase
            self::ToBill => __('purchase_orders.status.to_bill'),
            self::PartiallyBilled => __('purchase_orders.status.partially_billed'),
            self::FullyBilled => __('purchase_orders.status.fully_billed'),

            // Final states
            self::Done => __('purchase_orders.status.done'),
            self::Cancelled => __('purchase_orders.status.cancelled'),
        };
    }

    /**
     * Get the color associated with the status for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            // Pre-commitment phase
            self::RFQ => 'slate',
            self::RFQSent => 'gray',

            // Commitment phase
            self::Draft => 'gray',
            self::Sent => 'blue',
            self::Confirmed => 'indigo',

            // Fulfillment phase
            self::ToReceive => 'blue',
            self::PartiallyReceived => 'yellow',
            self::FullyReceived => 'emerald',

            // Billing phase
            self::ToBill => 'orange',
            self::PartiallyBilled => 'amber',
            self::FullyBilled => 'green',

            // Final states
            self::Done => 'green',
            self::Cancelled => 'red',
        };
    }

    /**
     * Check if the purchase order can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this, [self::RFQ, self::Draft]);
    }

    /**
     * Check if the RFQ can be sent to vendors.
     */
    public function canSendRFQ(): bool
    {
        return $this === self::RFQ;
    }

    /**
     * Check if the purchase order can be sent to vendor.
     */
    public function canBeSent(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if the purchase order can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        return in_array($this, [self::Draft, self::Sent]);
    }

    /**
     * Check if the purchase order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::RFQ,
            self::RFQSent,
            self::Draft,
            self::Sent,
            self::Confirmed,
            self::ToReceive,
            self::PartiallyReceived
        ]);
    }

    /**
     * Check if goods can be received against this purchase order.
     */
    public function canReceiveGoods(): bool
    {
        return in_array($this, [
            self::Confirmed,
            self::ToReceive,
            self::PartiallyReceived,
            self::ToBill,
            self::PartiallyBilled
        ]);
    }

    /**
     * Check if vendor bills can be created against this purchase order.
     */
    public function canCreateBill(): bool
    {
        return in_array($this, [
            self::PartiallyReceived,
            self::FullyReceived,
            self::ToBill,
            self::PartiallyBilled
        ]);
    }

    /**
     * Get all statuses that represent active purchase orders.
     */
    public static function activeStatuses(): array
    {
        return [
            self::Confirmed,
            self::ToReceive,
            self::PartiallyReceived,
            self::FullyReceived,
            self::ToBill,
            self::PartiallyBilled,
        ];
    }

    /**
     * Get all statuses that represent completed purchase orders.
     */
    public static function completedStatuses(): array
    {
        return [
            self::FullyBilled,
            self::Done,
            self::Cancelled,
        ];
    }

    /**
     * Get all statuses in the pre-commitment phase.
     */
    public static function preCommitmentStatuses(): array
    {
        return [
            self::RFQ,
            self::RFQSent,
        ];
    }

    /**
     * Get all statuses in the commitment phase.
     */
    public static function commitmentStatuses(): array
    {
        return [
            self::Draft,
            self::Sent,
            self::Confirmed,
        ];
    }

    /**
     * Get all statuses in the fulfillment phase.
     */
    public static function fulfillmentStatuses(): array
    {
        return [
            self::ToReceive,
            self::PartiallyReceived,
            self::FullyReceived,
        ];
    }

    /**
     * Get all statuses in the billing phase.
     */
    public static function billingStatuses(): array
    {
        return [
            self::ToBill,
            self::PartiallyBilled,
            self::FullyBilled,
        ];
    }

    /**
     * Check if this status represents a committed purchase order.
     */
    public function isCommitted(): bool
    {
        return !in_array($this, [self::RFQ, self::RFQSent, self::Cancelled]);
    }

    /**
     * Check if this status allows receiving goods.
     */
    public function allowsReceiving(): bool
    {
        return $this->canReceiveGoods();
    }

    /**
     * Check if this status allows billing.
     */
    public function allowsBilling(): bool
    {
        return $this->canCreateBill();
    }
}
