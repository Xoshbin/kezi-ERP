<?php

namespace Jmeryar\Inventory\Tests\Feature\Actions\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Inventory\Actions\Inventory\ConfirmStockMoveAction;
use Jmeryar\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Models\StockMove;
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
        'move_type' => \Jmeryar\Inventory\Enums\Inventory\StockMoveType::InternalTransfer, // Use internal transfer to skip valuation
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
