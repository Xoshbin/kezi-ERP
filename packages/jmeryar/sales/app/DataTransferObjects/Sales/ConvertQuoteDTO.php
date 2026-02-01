<?php

namespace Jmeryar\Sales\DataTransferObjects\Sales;

/**
 * Data Transfer Object for converting a Quote to Sales Order or Invoice
 */
readonly class ConvertQuoteDTO
{
    public function __construct(
        public int $quoteId,
        public string $convertTo, // 'sales_order' or 'invoice'
        public ?int $convertedByUserId = null,
    ) {}

    /**
     * Check if converting to sales order.
     */
    public function isConvertingToSalesOrder(): bool
    {
        return $this->convertTo === 'sales_order';
    }

    /**
     * Check if converting to invoice.
     */
    public function isConvertingToInvoice(): bool
    {
        return $this->convertTo === 'invoice';
    }
}
