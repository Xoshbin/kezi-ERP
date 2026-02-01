<?php

namespace Jmeryar\Inventory\DataTransferObjects\Inventory;

use Carbon\Carbon;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockMoveType;

readonly class UpdateStockMoveWithProductLinesDTO
{
    /**
     * @param  array<CreateStockMoveProductLineDTO>  $product_lines
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
