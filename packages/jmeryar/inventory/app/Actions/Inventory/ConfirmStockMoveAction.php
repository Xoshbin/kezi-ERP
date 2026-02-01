<?php

namespace Jmeryar\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Jmeryar\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Services\Inventory\StockMoveService;

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
