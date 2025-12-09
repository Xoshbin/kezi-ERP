<?php

namespace Modules\Sales\Enums\Sales;

/**
 * Sales Order Status Enum
 *
 * Defines the complete lifecycle states of a sales order from quotation through final closure.
 * This enum implements the comprehensive sales workflow with proper status tracking
 * for quotations, commitments, delivery, invoicing, and completion phases.
 */
use Filament\Support\Contracts\HasLabel;

enum SalesOrderStatus: string implements HasLabel
{
    // Pre-commitment phase
    case Quotation = 'quotation';                   // Initial quotation/estimate
    case QuotationSent = 'quotation_sent';          // Quotation sent to customer

    // Commitment phase
    case Draft = 'draft';                           // SO being prepared
    case Sent = 'sent';                             // SO sent to customer
    case Confirmed = 'confirmed';                   // SO confirmed by customer

    // Fulfillment phase
    case ToDeliver = 'to_deliver';                  // Ready for delivery
    case PartiallyDelivered = 'partially_delivered'; // Some items delivered
    case FullyDelivered = 'fully_delivered';        // All items delivered

    // Invoicing phase
    case ToInvoice = 'to_invoice';                  // Ready for invoicing
    case PartiallyInvoiced = 'partially_invoiced';  // Some items invoiced
    case FullyInvoiced = 'fully_invoiced';          // All items invoiced

    // Final states
    case Done = 'done';                             // Completed (delivered & invoiced)
    case Cancelled = 'cancelled';                   // Cancelled

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            // Pre-commitment phase
            self::Quotation => __('sales::sales_orders.statuses.quotation'),
            self::QuotationSent => __('sales::sales_orders.statuses.quotation_sent'),

            // Commitment phase
            self::Draft => __('sales::sales_orders.statuses.draft'),
            self::Sent => __('sales::sales_orders.statuses.sent'),
            self::Confirmed => __('sales::sales_orders.statuses.confirmed'),

            // Fulfillment phase
            self::ToDeliver => __('sales::sales_orders.statuses.to_deliver'),
            self::PartiallyDelivered => __('sales::sales_orders.statuses.partially_delivered'),
            self::FullyDelivered => __('sales::sales_orders.statuses.fully_delivered'),

            // Invoicing phase
            self::ToInvoice => __('sales::sales_orders.statuses.to_invoice'),
            self::PartiallyInvoiced => __('sales::sales_orders.statuses.partially_invoiced'),
            self::FullyInvoiced => __('sales::sales_orders.statuses.fully_invoiced'),

            // Final states
            self::Done => __('sales::sales_orders.statuses.done'),
            self::Cancelled => __('sales::sales_orders.statuses.cancelled'),
        };
    }

    /**
     * Get the color associated with the status for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            // Pre-commitment phase
            self::Quotation => 'slate',
            self::QuotationSent => 'gray',

            // Commitment phase
            self::Draft => 'gray',
            self::Sent => 'blue',
            self::Confirmed => 'indigo',

            // Fulfillment phase
            self::ToDeliver => 'blue',
            self::PartiallyDelivered => 'yellow',
            self::FullyDelivered => 'emerald',

            // Invoicing phase
            self::ToInvoice => 'orange',
            self::PartiallyInvoiced => 'amber',
            self::FullyInvoiced => 'green',

            // Final states
            self::Done => 'green',
            self::Cancelled => 'red',
        };
    }

    /**
     * Check if the sales order can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this, [self::Quotation, self::Draft]);
    }

    /**
     * Check if the sales order can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        return in_array($this, [self::Quotation, self::QuotationSent, self::Draft, self::Sent]);
    }

    /**
     * Check if the sales order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return ! in_array($this, [self::Done, self::Cancelled]);
    }

    /**
     * Check if goods can be delivered against this sales order.
     */
    public function canDeliverGoods(): bool
    {
        return in_array($this, [
            self::Confirmed,
            self::ToDeliver,
            self::PartiallyDelivered,
            self::ToInvoice,
            self::PartiallyInvoiced,
        ]);
    }

    /**
     * Check if customer invoices can be created against this sales order.
     */
    public function canCreateInvoice(): bool
    {
        return in_array($this, [
            self::Confirmed,
            self::ToDeliver,
            self::PartiallyDelivered,
            self::FullyDelivered,
            self::ToInvoice,
            self::PartiallyInvoiced,
        ]);
    }

    /**
     * Get the next logical status after confirming the sales order.
     */
    public function getNextStatusAfterConfirmation(): self
    {
        return self::ToDeliver;
    }

    /**
     * Get all statuses that represent active sales orders.
     */
    public static function activeStatuses(): array
    {
        return [
            self::Confirmed,
            self::ToDeliver,
            self::PartiallyDelivered,
            self::FullyDelivered,
            self::ToInvoice,
            self::PartiallyInvoiced,
        ];
    }

    /**
     * Get all statuses that represent completed sales orders.
     */
    public static function completedStatuses(): array
    {
        return [
            self::FullyInvoiced,
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
            self::Quotation,
            self::QuotationSent,
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
            self::ToDeliver,
            self::PartiallyDelivered,
            self::FullyDelivered,
        ];
    }

    /**
     * Get all statuses in the invoicing phase.
     */
    public static function invoicingStatuses(): array
    {
        return [
            self::ToInvoice,
            self::PartiallyInvoiced,
            self::FullyInvoiced,
        ];
    }

    /**
     * Check if this status represents a committed sales order.
     */
    public function isCommitted(): bool
    {
        return ! in_array($this, [self::Quotation, self::QuotationSent, self::Cancelled]);
    }

    /**
     * Check if this status allows delivering goods.
     */
    public function allowsDelivery(): bool
    {
        return $this->canDeliverGoods();
    }

    /**
     * Check if this status allows creating invoices.
     */
    public function allowsInvoicing(): bool
    {
        return $this->canCreateInvoice();
    }

    /**
     * Get the numeric order/priority of this status for progression validation.
     * Lower numbers come before higher numbers in the workflow.
     */
    public function getOrder(): int
    {
        return match ($this) {
            // Pre-commitment phase (0-9)
            self::Quotation => 0,
            self::QuotationSent => 1,

            // Commitment phase (10-19)
            self::Draft => 10,
            self::Sent => 11,
            self::Confirmed => 12,

            // Fulfillment phase (20-29)
            self::ToDeliver => 20,
            self::PartiallyDelivered => 21,
            self::FullyDelivered => 22,

            // Invoicing phase (30-39)
            self::ToInvoice => 30,
            self::PartiallyInvoiced => 31,
            self::FullyInvoiced => 32,

            // Final states (40+)
            self::Done => 40,
            self::Cancelled => 99, // Special case - can be reached from many states
        };
    }

    /**
     * Check if this status can transition to another status.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        // Can always cancel (except if already cancelled or done)
        if ($newStatus === self::Cancelled) {
            return ! in_array($this, [self::Done, self::Cancelled]);
        }

        // Can't transition from cancelled or done
        if (in_array($this, [self::Cancelled, self::Done])) {
            return false;
        }

        // Generally, can only move forward in the workflow
        return $newStatus->getOrder() >= $this->getOrder();
    }

    /**
     * Get valid transitions from this status.
     *
     * @return array<self>
     */
    public function getValidTransitions(): array
    {
        $validTransitions = [];

        foreach (self::cases() as $status) {
            if ($this->canTransitionTo($status)) {
                $validTransitions[] = $status;
            }
        }

        return $validTransitions;
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }
}
