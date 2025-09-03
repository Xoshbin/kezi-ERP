<?php

namespace App\DataTransferObjects\Inventory;

readonly class ConfirmStockMoveDTO
{
    public function __construct(
        public int $stock_move_id,
    ) {}
}
