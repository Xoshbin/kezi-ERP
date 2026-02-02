<?php

namespace Kezi\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Kezi\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Services\Inventory\StockMoveService;

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
