<?php

namespace Modules\Inventory\Actions\Inventory;

use App\DataTransferObjects\Inventory\UpdateStockMoveDTO;
use App\Models\StockMove;
use App\Services\Inventory\StockMoveService;
use Illuminate\Support\Facades\DB;

class UpdateStockMoveAction
{
    public function __construct(protected StockMoveService $stockMoveService) {}

    public function execute(UpdateStockMoveDTO $dto): StockMove
    {
        return DB::transaction(function () use ($dto) {
            $stockMove = StockMove::findOrFail($dto->id);

            return $this->stockMoveService->updateMove($stockMove, $dto);
        });
    }
}
