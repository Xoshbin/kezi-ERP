<?php

use App\Models\Company;
use App\Models\User;
use Modules\Inventory\Models\Lot;
use Modules\Inventory\Models\StockLocation;
use Modules\Product\Models\Product;
use Modules\QualityControl\Actions\RejectLotAction;
use Modules\QualityControl\DataTransferObjects\RejectLotDTO;

it('rejects a lot with reason and quarantine location', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);
    $quarantineLocation = StockLocation::factory()->create([
        'company_id' => $company->id,
        'name' => 'Quarantine Zone',
    ]);

    $lot = Lot::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'lot_code' => 'LOT-001',
        'active' => true,
    ]);

    $dto = new RejectLotDTO(
        lotId: $lot->id,
        rejectionReason: 'Failed quality inspection - scratches detected',
        quarantineLocationId: $quarantineLocation->id,
    );

    $action = app(RejectLotAction::class);
    $result = $action->execute($dto);

    expect($result)
        ->is_rejected->toBe(1)
        ->rejection_reason->toBe('Failed quality inspection - scratches detected')
        ->quarantine_location_id->toBe($quarantineLocation->id)
        ->active->toBeFalse(); // Rejected lots are deactivated

    expect($result->isRejected())->toBeTrue();
});

it('deactivates lot when rejected', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);

    $lot = Lot::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'active' => true,
    ]);

    expect($lot->active)->toBeTrue();

    $dto = new RejectLotDTO(
        lotId: $lot->id,
        rejectionReason: 'Quality check failed',
    );

    $action = app(RejectLotAction::class);
    $result = $action->execute($dto);

    expect($result->active)->toBeFalse();
});
