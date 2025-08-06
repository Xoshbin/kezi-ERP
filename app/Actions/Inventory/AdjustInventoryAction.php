<?php

namespace App\Actions\Inventory;

use App\DataTransferObjects\Inventory\AdjustInventoryDTO;
use App\Services\Inventory\InventoryValuationService;
use Illuminate\Support\Facades\DB;

class AdjustInventoryAction
{
    public function __construct(protected InventoryValuationService $inventoryValuationService)
    {
    }

    public function execute(AdjustInventoryDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $this->inventoryValuationService->adjustInventoryValue($dto);
        });
    }
}
