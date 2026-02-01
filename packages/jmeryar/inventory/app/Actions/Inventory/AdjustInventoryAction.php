<?php

namespace Jmeryar\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Jmeryar\Inventory\DataTransferObjects\Inventory\AdjustInventoryDTO;
use Jmeryar\Inventory\Services\Inventory\InventoryValuationService;

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
