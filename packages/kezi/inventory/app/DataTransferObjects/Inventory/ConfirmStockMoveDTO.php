<?php

namespace Kezi\Inventory\DataTransferObjects\Inventory;

readonly class ConfirmStockMoveDTO
{
    public function __construct(
        public int $stock_move_id,
    ) {}
}
