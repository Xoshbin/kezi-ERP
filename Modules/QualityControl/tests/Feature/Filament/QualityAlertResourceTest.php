<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Models\Product;
use Modules\QualityControl\Enums\QualityAlertStatus;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource\Pages\CreateQualityAlert;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource\Pages\EditQualityAlert;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource\Pages\ListQualityAlerts;
use Modules\QualityControl\Models\DefectType;
use Modules\QualityControl\Models\QualityAlert;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can render quality alert list page', function () {
    livewire(ListQualityAlerts::class)
        ->assertSuccessful();
});

it('can create a quality alert', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $defectType = DefectType::factory()->create(['company_id' => $this->company->id]);

    livewire(CreateQualityAlert::class)
        ->fillForm([
            'product_id' => $product->id,
            'defect_type_id' => $defectType->id,
            'description' => 'Product quality issue detected',
            'status' => QualityAlertStatus::New,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $alert = QualityAlert::where('description', 'Product quality issue detected')->first();

    expect($alert)
        ->not->toBeNull()
        ->status->toBe(QualityAlertStatus::New)
        ->product_id->toBe($product->id)
        ->company_id->toBe($this->company->id)
        ->reported_by_user_id->toBe($this->user->id);
});

it('validates required fields when creating quality alert', function () {
    livewire(CreateQualityAlert::class)
        ->fillForm([
            'description' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['description']);
});

it('can edit a quality alert and add CAPA', function () {
    $alert = QualityAlert::factory()->create([
        'company_id' => $this->company->id,
        'status' => QualityAlertStatus::New,
        'description' => 'Original issue',
    ]);

    livewire(EditQualityAlert::class, [
        'record' => $alert->getRouteKey(),
    ])
        ->fillForm([
            'status' => QualityAlertStatus::InProgress,
            'root_cause' => 'Identified root cause',
            'corrective_action' => 'Immediate fix applied',
            'preventive_action' => 'Process improved',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($alert->fresh())
        ->status->toBe(QualityAlertStatus::InProgress)
        ->root_cause->toBe('Identified root cause')
        ->corrective_action->toBe('Immediate fix applied')
        ->preventive_action->toBe('Process improved');
});

it('can list quality alerts in table', function () {
    $alerts = QualityAlert::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListQualityAlerts::class)
        ->assertCanSeeTableRecords($alerts);
});

it('can filter quality alerts by status', function () {
    $newAlert = QualityAlert::factory()->create([
        'company_id' => $this->company->id,
        'status' => QualityAlertStatus::New,
    ]);

    $resolvedAlert = QualityAlert::factory()->create([
        'company_id' => $this->company->id,
        'status' => QualityAlertStatus::Resolved,
    ]);

    livewire(ListQualityAlerts::class)
        ->filterTable('status', QualityAlertStatus::New->value)
        ->assertCanSeeTableRecords([$newAlert])
        ->assertCanNotSeeTableRecords([$resolvedAlert]);
});

it('displays navigation badge with new alert count', function () {
    QualityAlert::factory()->count(5)->create([
        'company_id' => $this->company->id,
        'status' => QualityAlertStatus::New,
    ]);

    QualityAlert::factory()->count(2)->create([
        'company_id' => $this->company->id,
        'status' => QualityAlertStatus::Resolved,
    ]);

    $badge = QualityAlertResource::getNavigationBadge();

    expect($badge)->toBe('5');
});
