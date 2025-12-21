<?php

namespace Modules\Purchase\DataTransferObjects\Purchases;

use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;

/**
 * Data Transfer Object for updating an existing Purchase Order
 */
class UpdatePurchaseOrderDTO
{
    /**
     * @param  PurchaseOrderLineDTO[]  $lines
     */
    public function __construct(
        public readonly PurchaseOrder $purchaseOrder,
        public readonly int $vendor_id,
        public readonly int $currency_id,
        public readonly string $po_date,
        public readonly array $lines,
        public readonly ?string $reference = null,
        public readonly ?string $expected_delivery_date = null,
        public readonly ?float $exchange_rate_at_creation = null,
        public readonly ?string $notes = null,
        public readonly ?string $terms_and_conditions = null,
        public readonly ?int $delivery_location_id = null,
        public readonly ?PurchaseOrderStatus $status = null,
    ) {}
}
