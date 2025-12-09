<?php

namespace Modules\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\Inventory\UpdateStockMoveWithProductLinesDTO;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Services\Inventory\StockMoveService;

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
