<?php

namespace Modules\Inventory\Actions\Inventory;

use App\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use App\Models\StockMove;
use App\Services\Inventory\StockMoveService;
use Illuminate\Support\Facades\DB;

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
