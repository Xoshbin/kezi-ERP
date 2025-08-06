<?php

namespace App\DataTransferObjects\Inventory;

use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use Carbon\Carbon;

readonly class CreateStockMoveDTO
{
    public function __construct(
        public int $company_id,
        public int $product_id,
        public float $quantity,
        public int $from_location_id,
        public int $to_location_id,
        public StockMoveType $move_type,
        public StockMoveStatus $status,
        public Carbon $move_date,
        public int $created_by_user_id,
        public ?string $reference = null,
        public ?string $source_type = null,
        public ?int $source_id = null,
    ) {
    }
}
