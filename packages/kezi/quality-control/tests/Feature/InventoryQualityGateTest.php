<?php

namespace Kezi\QualityControl\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveProductLine;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Product\Models\Product;
use Kezi\QualityControl\Enums\QualityCheckStatus;
use Kezi\QualityControl\Models\QualityCheck;
use Tests\TestCase;
use Tests\Traits\WithConfiguredCompany;

class InventoryQualityGateTest extends TestCase
{
    use RefreshDatabase, WithConfiguredCompany;

    protected \App\Models\Company $company;

    protected \App\Models\User $user;

    private StockPicking $picking;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupWithConfiguredCompany();

        $this->product = Product::factory()->create(['company_id' => $this->company->id]);

        $sourceLocation = StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockLocationType::Internal,
        ]);
        $destLocation = StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockLocationType::Internal,
        ]);

        $this->picking = StockPicking::factory()->create([
            'company_id' => $this->company->id,
            'state' => StockPickingState::Assigned,
        ]);

        $move = StockMove::create([
            'company_id' => $this->company->id,
            'picking_id' => $this->picking->id,
            'status' => \Kezi\Inventory\Enums\Inventory\StockMoveStatus::Confirmed,
            'move_type' => \Kezi\Inventory\Enums\Inventory\StockMoveType::Incoming,
            'move_date' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        StockMoveProductLine::create([
            'company_id' => $this->company->id,
            'stock_move_id' => $move->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'from_location_id' => $sourceLocation->id,
            'to_location_id' => $destLocation->id,
        ]);
    }

    public function test_allows_validation_if_no_quality_checks_exist(): void
    {
        $this->validatePicking();

        $this->picking->refresh();
        $this->assertEquals(StockPickingState::Done, $this->picking->state);
    }

    public function test_blocks_validation_if_a_mandatory_check_is_pending(): void
    {
        QualityCheck::factory()->create([
            'company_id' => $this->company->id,
            'source_type' => StockPicking::class,
            'source_id' => $this->picking->id,
            'status' => QualityCheckStatus::Draft,
            'is_blocking' => true,
            'product_id' => $this->product->id,
            // 'control_point_id' => 1, // Remove if not in factory or model
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage(__('qualitycontrol::check.quality_gate_failed'));

        $this->validatePicking();
    }

    public function test_blocks_validation_if_a_mandatory_check_is_failed(): void
    {
        QualityCheck::factory()->create([
            'company_id' => $this->company->id,
            'source_type' => StockPicking::class,
            'source_id' => $this->picking->id,
            'status' => QualityCheckStatus::Failed,
            'is_blocking' => true,
            'product_id' => $this->product->id,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage(__('qualitycontrol::check.quality_gate_failed'));

        $this->validatePicking();
    }

    public function test_allows_validation_if_all_mandatory_checks_are_passed(): void
    {
        QualityCheck::factory()->create([
            'company_id' => $this->company->id,
            'source_type' => StockPicking::class,
            'source_id' => $this->picking->id,
            'status' => QualityCheckStatus::Passed,
            'is_blocking' => true,
            'product_id' => $this->product->id,
        ]);

        $this->validatePicking();

        $this->picking->refresh();
        $this->assertEquals(StockPickingState::Done, $this->picking->state);
    }

    public function test_allows_validation_if_checks_are_pending_but_not_blocking(): void
    {
        QualityCheck::factory()->create([
            'company_id' => $this->company->id,
            'source_type' => StockPicking::class,
            'source_id' => $this->picking->id,
            'status' => QualityCheckStatus::Draft,
            'is_blocking' => false,
            'product_id' => $this->product->id,
        ]);

        $this->validatePicking();

        $this->picking->refresh();
        $this->assertEquals(StockPickingState::Done, $this->picking->state);
    }

    private function validatePicking(): void
    {
        // For now, we'll try to call the action directly once we create it.
        // Or if we implement it in the page, we'd need to mock the Filament page or just test the underlying logic.
        // Let's assume we will have a ValidateStockPickingAction.

        $data = [
            'moves' => $this->picking->stockMoves->flatMap(function ($move) {
                return $move->productLines->map(function ($line) use ($move) {
                    return [
                        'move_id' => $move->id,
                        'product_line_id' => $line->id,
                        'planned_quantity' => $line->quantity,
                        'actual_quantity' => $line->quantity,
                    ];
                });
            })->toArray(),
        ];

        app(\Kezi\Inventory\Actions\Inventory\ValidateStockPickingAction::class)->execute($this->picking, $data, false);
    }
}
