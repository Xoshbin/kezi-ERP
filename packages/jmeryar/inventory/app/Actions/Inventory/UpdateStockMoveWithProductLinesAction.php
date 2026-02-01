<?php

namespace Jmeryar\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Jmeryar\Inventory\DataTransferObjects\Inventory\UpdateStockMoveWithProductLinesDTO;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Services\Inventory\StockMoveService;

class UpdateStockMoveWithProductLinesAction
{
    public function __construct(protected StockMoveService $stockMoveService) {}

    public function execute(UpdateStockMoveWithProductLinesDTO $dto): StockMove
    {
        return DB::transaction(function () use ($dto) {
            $stockMove = StockMove::findOrFail($dto->id);

            return $this->stockMoveService->updateMoveWithProductLines($stockMove, $dto);
        });
    }
}
