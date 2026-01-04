<?php

namespace Modules\Inventory\DataTransferObjects;

use Modules\Inventory\Models\StockLocation;
use Modules\Purchase\Models\PurchaseOrder;

/**
 * DTO for creating a Goods Receipt (StockPicking) from a Purchase Order.
 */
readonly class ReceiveGoodsFromPurchaseOrderDTO
{
    /**
     * @param  PurchaseOrder  $purchaseOrder  The PO to receive goods for
     * @param  int  $userId  The user initiating the receipt
     * @param  \DateTimeInterface|null  $receiptDate  Optional receipt date (defaults to now)
     * @param  StockLocation|null  $location  Optional destination location (defaults to company default)
     */
    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public int $userId,
        public ?\DateTimeInterface $receiptDate = null,
        public ?StockLocation $location = null,
    ) {}
}
