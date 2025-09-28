<?php

namespace Modules\Inventory\DataTransferObjects\Inventory;



use Carbon\Carbon;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;

readonly class CreateStockMoveDTO
{
    /**
     * @param array<CreateStockMoveProductLineDTO> $product_lines
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
