<?php

namespace Jmeryar\Inventory\DataTransferObjects;

use Jmeryar\Inventory\Models\StockPicking;

/**
 * DTO for validating a Goods Receipt (StockPicking).
 */
readonly class ValidateGoodsReceiptDTO
{
    /**
     * @param  StockPicking  $stockPicking  The picking to validate
     * @param  int  $userId  The user validating the receipt
     * @param  array<ReceiveGoodsLineDTO>  $lines  The lines with actual received quantities
     * @param  bool  $createBackorder  Whether to create a backorder for partial receipts
     */
    public function __construct(
        public StockPicking $stockPicking,
        public int $userId,
        public array $lines = [],
        public bool $createBackorder = true,
    ) {}
}
