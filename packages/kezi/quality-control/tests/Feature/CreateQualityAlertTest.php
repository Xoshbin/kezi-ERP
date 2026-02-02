<?php

use App\Models\Company;
use App\Models\User;
use Kezi\Product\Models\Product;
use Kezi\QualityControl\Actions\CreateQualityAlertAction;
use Kezi\QualityControl\DataTransferObjects\CreateQualityAlertDTO;
use Kezi\QualityControl\Enums\QualityAlertStatus;
use Kezi\QualityControl\Models\DefectType;
use Kezi\QualityControl\Models\QualityAlert;

it('creates a quality alert from DTO', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);
    $defectType = DefectType::factory()->create([
        'company_id' => $company->id,
        'code' => 'SCRATCH',
        'name' => 'Surface Scratch',
    ]);

    $dto = new CreateQualityAlertDTO(
        companyId: $company->id,
        qualityCheckId: null,
        productId: $product->id,
        lotId: null,
        serialNumberId: null,
        defectTypeId: $defectType->id,
        description: 'Minor scratch on surface',
        reportedByUserId: $user->id,
    );

    $action = app(CreateQualityAlertAction::class);
    $alert = $action->execute($dto);

    expect($alert)
        ->toBeInstanceOf(QualityAlert::class)
        ->number->toStartWith('QA-')
        ->status->toBe(QualityAlertStatus::New)
        ->company_id->toBe($company->id)
        ->product_id->toBe($product->id)
        ->defect_type_id->toBe($defectType->id)
        ->reported_by_user_id->toBe($user->id)
        ->description->toBe('Minor scratch on surface');
});

it('generates sequential alert numbers', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);

    $action = app(CreateQualityAlertAction::class);

    $alert1 = $action->execute(new CreateQualityAlertDTO(
        companyId: $company->id,
        qualityCheckId: null,
        productId: $product->id,
        lotId: null,
        serialNumberId: null,
        defectTypeId: null,
        description: 'Issue 1',
        reportedByUserId: $user->id,
    ));

    $alert2 = $action->execute(new CreateQualityAlertDTO(
        companyId: $company->id,
        qualityCheckId: null,
        productId: $product->id,
        lotId: null,
        serialNumberId: null,
        defectTypeId: null,
        description: 'Issue 2',
        reportedByUserId: $user->id,
    ));

    expect($alert1->number)->toBe('QA-000001');
    expect($alert2->number)->toBe('QA-000002');
});
