<?php

namespace Kezi\Inventory\Tests\Feature\Actions\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Actions\Inventory\ConfirmStockMoveAction;
use Kezi\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Models\StockMove;
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
        'move_type' => \Kezi\Inventory\Enums\Inventory\StockMoveType::InternalTransfer, // Use internal transfer to skip valuation
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
