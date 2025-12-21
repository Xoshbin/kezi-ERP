<?php

namespace Modules\Sales\DataTransferObjects\Sales;

use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Models\SalesOrder;

/**
 * Data Transfer Object for updating an existing Sales Order
 */
class UpdateSalesOrderDTO
{
    /**
     * @param  SalesOrderLineDTO[]  $lines
     */
    public function __construct(
        public readonly SalesOrder $salesOrder,
        public readonly int $customer_id,
        public readonly int $currency_id,
        public readonly string $so_date,
        public readonly array $lines,
        public readonly ?string $reference = null,
        public readonly ?string $expected_delivery_date = null,
        public readonly ?float $exchange_rate_at_creation = null,
        public readonly ?string $notes = null,
        public readonly ?string $terms_and_conditions = null,
        public readonly ?int $delivery_location_id = null,
        public readonly ?SalesOrderStatus $status = null,
    ) {}
}
