<?php

namespace App\DataTransferObjects\Inventory;

use Carbon\Carbon;

readonly class CreateInterCompanyTransferDTO
{
    public function __construct(
        public int $source_company_id,
        public int $target_company_id,
        public int $product_id,
        public float $quantity,
        public Carbon $transfer_date,
        public int $created_by_user_id,
        public ?string $reference = null,
        public ?int $source_stock_move_id = null,
        public ?\Brick\Money\Money $transfer_price = null,
        public ?string $notes = null,
    ) {}
}
