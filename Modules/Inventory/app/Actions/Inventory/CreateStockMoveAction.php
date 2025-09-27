<?php

namespace Modules\Inventory\Actions\Inventory;

use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\Models\StockMove;
use App\Services\Inventory\StockMoveService;
use Illuminate\Support\Facades\DB;

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
