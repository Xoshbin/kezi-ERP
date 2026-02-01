<?php

namespace Jmeryar\Inventory\Enums\Inventory;

/**
 * Enum representing the source of cost information for inventory valuation
 *
 * This enum tracks where the cost per unit was determined from,
 * providing transparency and audit trail for cost calculations.
 */
enum CostSource: string
{
    case VendorBill = 'vendor_bill';
    case AverageCost = 'average_cost';
    case CostLayer = 'cost_layer';
    case UnitPrice = 'unit_price';
    case Manual = 'manual';
    case CompanyDefault = 'company_default';

    /**
     * Get the translated label for the cost source
     */
    public function label(): string
    {
        return __('enums.cost_source.'.$this->value);
    }

    /**
     * Get a description of what this cost source represents
     */
    public function description(): string
    {
        return match ($this) {
            self::VendorBill => __('Cost derived from vendor bill line including non-recoverable taxes'),
            self::AverageCost => __('Cost from product average cost (AVCO method)'),
            self::CostLayer => __('Cost from inventory cost layer (FIFO/LIFO method)'),
            self::UnitPrice => __('Cost fallback to product unit price'),
            self::Manual => __('Cost manually entered by user'),
            self::CompanyDefault => __('Cost from company default settings'),
        };
    }

    /**
     * Get the priority/reliability of this cost source (lower = more reliable)
     */
    public function priority(): int
    {
        return match ($this) {
            self::VendorBill => 1,      // Most reliable - actual purchase cost
            self::AverageCost => 2,     // Reliable - calculated from purchases
            self::CostLayer => 3,       // Reliable - historical purchase cost
            self::UnitPrice => 4,       // Less reliable - sales price used as cost
            self::Manual => 5,          // User input - depends on user accuracy
            self::CompanyDefault => 6,  // Least reliable - generic fallback
        };
    }

    /**
     * Check if this cost source is considered reliable for accounting purposes
     */
    public function isReliable(): bool
    {
        return $this->priority() <= 3;
    }

    /**
     * Check if this cost source requires special approval or warnings
     */
    public function requiresWarning(): bool
    {
        return $this->priority() >= 4;
    }
}
