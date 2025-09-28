<?php

namespace Modules\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Services\Inventory\InventoryValuationService;
use Modules\Inventory\DataTransferObjects\Inventory\AdjustInventoryDTO;

class AdjustInventoryAction
{
    public function __construct(protected InventoryValuationService $inventoryValuationService) {}

    public function execute(AdjustInventoryDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $this->inventoryValuationService->adjustInventoryValue($dto);
        });
    }
}
