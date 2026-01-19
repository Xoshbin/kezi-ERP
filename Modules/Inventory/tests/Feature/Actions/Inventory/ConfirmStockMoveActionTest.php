<?php

namespace Modules\Inventory\Tests\Feature\Actions\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Actions\Inventory\ConfirmStockMoveAction;
use Modules\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Events\Inventory\StockMoveConfirmed;
use Modules\Inventory\Models\StockMove;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->action = app(ConfirmStockMoveAction::class);
});

it('confirms a stock move', function () {
    Event::fake();

    $move = StockMove::factory()->create([
        'company_id' => $this->company->id,
        'status' => StockMoveStatus::Draft,
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

    Event::assertDispatched(StockMoveConfirmed::class, function ($event) use ($move) {
        return $event->stockMove->id === $move->id;
    });
});
