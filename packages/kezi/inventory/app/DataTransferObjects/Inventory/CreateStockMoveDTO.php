<?php

namespace Kezi\Inventory\DataTransferObjects\Inventory;

use Carbon\Carbon;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;

readonly class CreateStockMoveDTO
{
    /**
     * @param  array<CreateStockMoveProductLineDTO>  $product_lines
     */
    public function __construct(
        public int $company_id,
        public StockMoveType $move_type,
        public StockMoveStatus $status,
        public Carbon $move_date,
        public int $created_by_user_id,
        public array $product_lines,
        public ?string $reference = null,
        public ?string $description = null,
        public ?string $source_type = null,
        public ?int $source_id = null,
    ) {}
}
