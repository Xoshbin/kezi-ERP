<?php

namespace Modules\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Services\Inventory\StockMoveService;

class ConfirmStockMoveAction
{
    public function __construct(protected StockMoveService $stockMoveService) {}

    public function execute(ConfirmStockMoveDTO $dto): StockMove
    {
        return DB::transaction(function () use ($dto) {
            return $this->stockMoveService->confirmMove($dto);
        });
    }
}
