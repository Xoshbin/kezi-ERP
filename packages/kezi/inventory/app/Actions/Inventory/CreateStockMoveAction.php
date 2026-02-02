<?php

namespace Kezi\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Services\Inventory\StockMoveService;

class CreateStockMoveAction
{
    public function __construct(protected StockMoveService $stockMoveService) {}

    public function execute(CreateStockMoveDTO $dto): StockMove
    {
        return DB::transaction(function () use ($dto) {
            return $this->stockMoveService->createMove($dto);
        });
    }
}
