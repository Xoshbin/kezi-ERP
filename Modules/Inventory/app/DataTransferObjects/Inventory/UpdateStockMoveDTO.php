<?php

namespace App\DataTransferObjects\Inventory;

use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use Carbon\Carbon;

readonly class UpdateStockMoveDTO
{
    public function __construct(
        public int $id,
        public int $company_id,
        public int $product_id,
        public float $quantity,
        public int $from_location_id,
        public int $to_location_id,
        public StockMoveType $move_type,
        public StockMoveStatus $status,
        public Carbon $move_date,
        public ?string $reference = null,
    ) {}
}
