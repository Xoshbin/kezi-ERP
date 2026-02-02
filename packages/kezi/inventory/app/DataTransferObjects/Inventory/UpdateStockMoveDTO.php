<?php

namespace Kezi\Inventory\DataTransferObjects\Inventory;

use Carbon\Carbon;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;

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
