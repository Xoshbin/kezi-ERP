<?php

namespace Modules\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Services\Inventory\StockMoveService;

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
