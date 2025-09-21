<?php

namespace App\Actions\Inventory;

use App\DataTransferObjects\Inventory\UpdateStockMoveWithProductLinesDTO;
use App\Models\StockMove;
use App\Services\Inventory\StockMoveService;
use Illuminate\Support\Facades\DB;

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
