<?php

namespace Kezi\QualityControl\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Models\Lot;
use Kezi\Product\Models\Product;
use Kezi\QualityControl\Enums\QualityAlertStatus;
use Kezi\QualityControl\Enums\QualityCheckStatus;
use Kezi\QualityControl\Models\QualityCheck;
use Tests\TestCase;
use Tests\Traits\WithConfiguredCompany;

class QualityAlertWorkflowTest extends TestCase
{
    use RefreshDatabase, WithConfiguredCompany;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupWithConfiguredCompany();
    }

    public function test_automatically_creates_a_quality_alert_when_a_blocking_check_fails(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        $lot = Lot::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
        ]);

        $qualityCheck = QualityCheck::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'lot_id' => $lot->id,
            'is_blocking' => true,
            'status' => QualityCheckStatus::Draft,
        ]);

        // Fail the check
        $qualityCheck->update([
            'status' => QualityCheckStatus::Failed,
            'notes' => 'Failed measurement',
            'inspected_by_user_id' => $user->id,
            'inspected_at' => now(),
        ]);

        $this->assertDatabaseHas('quality_alerts', [
            'company_id' => $this->company->id,
            'quality_check_id' => $qualityCheck->id,
            'product_id' => $product->id,
            'lot_id' => $lot->id,
            'status' => QualityAlertStatus::New->value,
            'description' => 'Quality Check Failed: Failed measurement',
        ]);
    }

    public function test_does_not_create_an_alert_if_the_check_is_not_blocking(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $qualityCheck = QualityCheck::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'is_blocking' => false,
            'status' => QualityCheckStatus::Draft,
        ]);

        $qualityCheck->update(['status' => QualityCheckStatus::Failed]);

        $this->assertDatabaseCount('quality_alerts', 0);
    }
}
