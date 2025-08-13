<?php

namespace App\DataTransferObjects\Inventory;

use App\Models\StockMove;

readonly class InterCompanyTransferResultDTO
{
    public function __construct(
        public StockMove $source_move,
        public StockMove $target_move,
        public string $transfer_reference,
        public \Brick\Money\Money $transfer_value,
        public bool $is_successful,
        public ?string $error_message = null,
    ) {}
}
