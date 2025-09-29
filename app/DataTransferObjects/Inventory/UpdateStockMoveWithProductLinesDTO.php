<?php

namespace App\DataTransferObjects\Inventory;

use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use Carbon\Carbon;

readonly class UpdateStockMoveWithProductLinesDTO
{
    /**
     * @param array<CreateStockMoveProductLineDTO> $product_lines
     */
    public function __construct(
        public int $id,
        public StockMoveType $move_type,
        public StockMoveStatus $status,
        public Carbon $move_date,
        public array $product_lines,
        public ?string $reference = null,
        public ?string $description = null,
        public ?string $source_type = null,
        public ?int $source_id = null,
    ) {}
}
