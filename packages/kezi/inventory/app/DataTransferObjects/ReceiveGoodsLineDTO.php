<?php

namespace Kezi\Inventory\DataTransferObjects;

/**
 * DTO for individual line item in a goods receipt.
 */
readonly class ReceiveGoodsLineDTO
{
    /**
     * @param  int  $purchaseOrderLineId  The PO line being received
     * @param  float  $quantityToReceive  The quantity being received in this operation
     * @param  int|null  $lotId  Optional lot/batch for tracking
     */
    public function __construct(
        public int $purchaseOrderLineId,
        public float $quantityToReceive,
        public ?int $lotId = null,
    ) {}
}
