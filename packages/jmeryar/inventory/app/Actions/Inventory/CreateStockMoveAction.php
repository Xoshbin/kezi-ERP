<?php

namespace Jmeryar\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Jmeryar\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Services\Inventory\StockMoveService;

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
