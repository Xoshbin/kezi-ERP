<?php

/** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */

namespace Jmeryar\QualityControl\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Product\Models\Product;
use Jmeryar\QualityControl\Enums\QualityTriggerFrequency;
use Jmeryar\QualityControl\Enums\QualityTriggerOperation;
use Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource;
use Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource\Pages\CreateQualityControlPoint;
use Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource\Pages\EditQualityControlPoint;
use Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource\Pages\ListQualityControlPoints;
use Jmeryar\QualityControl\Models\QualityControlPoint;
use Jmeryar\QualityControl\Models\QualityInspectionTemplate;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
    $this->actingAs($this->user);
});

describe('QualityControlPointResource', function () {
    it('can render list page', function () {
        $this->get(QualityControlPointResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can list quality control points', function () {
        $template = QualityInspectionTemplate::factory()->create(['company_id' => $this->company->id]);
        $record = QualityControlPoint::factory()
            ->for($this->company)
            ->for($template, 'inspectionTemplate')
            ->create(['name' => 'QC-POINT-TEST']);

        livewire(ListQualityControlPoints::class)
            ->assertCanRenderTableColumn('name')
            ->assertSee('QC-POINT-TEST');
    });

    it('can create quality control point', function () {
        $template = QualityInspectionTemplate::factory()->create(['company_id' => $this->company->id]);
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        livewire(CreateQualityControlPoint::class)
            ->fillForm([
                'name' => 'Test Control Point',
                'trigger_operation' => QualityTriggerOperation::GoodsReceipt->value,
                'trigger_frequency' => QualityTriggerFrequency::PerOperation->value,
                'inspection_template_id' => $template->id,
                'product_id' => $product->id,
                'is_blocking' => true,
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('quality_control_points', [
            'name' => 'Test Control Point',
            'company_id' => $this->company->id,
            'trigger_operation' => QualityTriggerOperation::GoodsReceipt->value,
        ]);
    });

    it('can edit quality control point', function () {
        $template = QualityInspectionTemplate::factory()->create(['company_id' => $this->company->id]);
        $record = QualityControlPoint::factory()
            ->for($this->company)
            ->for($template, 'inspectionTemplate')
            ->create(['name' => 'Old Name']);

        livewire(EditQualityControlPoint::class, [
            'record' => $record->getRouteKey(),
        ])
            ->fillForm([
                'name' => 'New Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($record->refresh())->name->toBe('New Name');
    });

    it('can delete quality control point', function () {
        $template = QualityInspectionTemplate::factory()->create(['company_id' => $this->company->id]);
        $record = QualityControlPoint::factory()
            ->for($this->company)
            ->for($template, 'inspectionTemplate')
            ->create();

        livewire(EditQualityControlPoint::class, [
            'record' => $record->getRouteKey(),
        ])
            ->callAction(\Filament\Actions\DeleteAction::class);

        $this->assertModelMissing($record);
    });

    it('can validate required fields', function () {
        livewire(CreateQualityControlPoint::class)
            ->fillForm([
                'name' => null,
                'trigger_operation' => null,
                'trigger_frequency' => null,
                'inspection_template_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'trigger_operation' => 'required',
                'trigger_frequency' => 'required',
                'inspection_template_id' => 'required',
            ]);
    });
});
