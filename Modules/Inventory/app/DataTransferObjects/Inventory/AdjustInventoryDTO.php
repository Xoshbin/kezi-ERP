<?php

namespace Modules\Inventory\DataTransferObjects\Inventory;

use Carbon\Carbon;

readonly class AdjustInventoryDTO
{
    public function __construct(
        public int $company_id,
        public int $product_id,
        public float $quantity,
        public int $location_id,
        public Carbon $adjustment_date,
        public int $created_by_user_id,
        public ?string $reason = null,
        public ?string $reference = null,
    ) {
    }
}
