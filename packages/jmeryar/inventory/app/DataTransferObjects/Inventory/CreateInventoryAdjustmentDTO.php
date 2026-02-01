<?php

namespace Jmeryar\Inventory\DataTransferObjects\Inventory;

use Carbon\Carbon;

readonly class CreateInventoryAdjustmentDTO
{
    /**
     * @param  array<InventoryAdjustmentLineDTO>  $lines
     */
    public function __construct(
        public int $company_id,
        public Carbon $adjustment_date,
        public string $reference,
        public string $reason,
        public array $lines,
        public int $created_by_user_id,
    ) {}
}
