<?php

namespace Kezi\QualityControl\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\Lot;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Product\Models\Product;
use Kezi\QualityControl\Actions\ResolveQualityAlertAction;
use Kezi\QualityControl\DataTransferObjects\ResolveQualityAlertDTO;
use Kezi\QualityControl\Enums\QualityAlertStatus;
use Kezi\QualityControl\Models\QualityAlert;
use Tests\TestCase;
use Tests\Traits\WithConfiguredCompany;

class QualityAlertResolutionTest extends TestCase
{
    use RefreshDatabase, WithConfiguredCompany;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupWithConfiguredCompany();
    }

    public function test_can_resolve_quality_alert_with_capa_info(): void
    {
        $alert = QualityAlert::factory()->create([
            'company_id' => $this->company->id,
            'status' => QualityAlertStatus::New,
        ]);

        $action = app(ResolveQualityAlertAction::class);
        $action->execute(new ResolveQualityAlertDTO(
            qualityAlertId: $alert->id,
            rootCause: 'Machine malfunction',
            correctiveAction: 'Repaired machine',
            preventiveAction: 'Scheduled maintenance',
            scrapItems: false,
        ));

        $alert->refresh();
        $this->assertEquals(QualityAlertStatus::Resolved, $alert->status);
        $this->assertEquals('Machine malfunction', $alert->root_cause);
        $this->assertNotNull($alert->resolved_at);
    }

    public function test_blocks_resolution_without_capa_via_observer(): void
    {
        $alert = QualityAlert::factory()->create([
            'company_id' => $this->company->id,
            'status' => QualityAlertStatus::New,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $alert->update(['status' => QualityAlertStatus::Resolved]);
    }

    public function test_triggers_scrap_action_when_resolving_with_scrap(): void
    {
        // Setup scrap account and location
        $scrapAccount = \Kezi\Accounting\Models\Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'expense',
        ]);
        $this->company->update(['default_scrap_account_id' => $scrapAccount->id]);

        StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockLocationType::Scrap,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'default_inventory_account_id' => \Kezi\Accounting\Models\Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_assets'])->id,
            'default_cogs_account_id' => \Kezi\Accounting\Models\Account::factory()->create(['company_id' => $this->company->id, 'type' => 'expense'])->id,
        ]);
        $lot = Lot::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
        ]);

        $this->seedStock($product, $this->company->defaultStockLocation, 10);

        $alert = QualityAlert::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'lot_id' => $lot->id,
            'status' => QualityAlertStatus::New,
        ]);

        $action = app(ResolveQualityAlertAction::class);
        $action->execute(new ResolveQualityAlertDTO(
            qualityAlertId: $alert->id,
            rootCause: 'Faulty batch',
            correctiveAction: 'Scrap items',
            preventiveAction: 'Better QC',
            scrapItems: true,
        ));

        $alert->refresh();
        $this->assertEquals(QualityAlertStatus::Resolved, $alert->status);

        $this->assertDatabaseHas('stock_moves', [
            'company_id' => $this->company->id,
            'source_type' => QualityAlert::class,
            'source_id' => $alert->id,
            'reference' => 'SCRAP-'.$alert->number,
        ]);
    }
}
