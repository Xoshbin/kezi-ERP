<?php

namespace Modules\Inventory\Tests\Feature\Actions\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Actions\Inventory\ConfirmStockMoveAction;
use Modules\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Models\StockMove;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->action = app(ConfirmStockMoveAction::class);
});

it('confirms a stock move', function () {
    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'status' => StockMoveStatus::Draft,
        'move_type' => \Modules\Inventory\Enums\Inventory\StockMoveType::InternalTransfer, // Use internal transfer to skip valuation
    ]);

    $dto = new ConfirmStockMoveDTO(
        stock_move_id: $move->id
    );

    $confirmedMove = $this->action->execute($dto);

    expect($confirmedMove->id)->toBe($move->id)
        ->and($confirmedMove->status)->toBe(StockMoveStatus::Done);

    $this->assertDatabaseHas('stock_moves', [
        'id' => $move->id,
        'status' => StockMoveStatus::Done,
    ]);
});
