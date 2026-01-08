<?php

use Modules\Inventory\Models\StockPicking;
use Modules\Product\Models\Product;
use Modules\QualityControl\Actions\CreateQualityCheckAction;
use Modules\QualityControl\DataTransferObjects\CreateQualityCheckDTO;
use Modules\QualityControl\Enums\QualityCheckStatus;
use Modules\QualityControl\Models\QualityCheck;

it('creates a quality check from DTO', function () {
    $company = createCompanyWithRequiredAccounts();
    $user = createUserForCompany($company);

    actingAs($user);

    $product = Product::factory()->forCompany($company)->create();
    $picking = StockPicking::factory()->forCompany($company)->create();

    $dto = new CreateQualityCheckDTO(
        companyId: $company->id,
        sourceType: get_class($picking),
        sourceId: $picking->id,
        productId: $product->id,
    );

    $action = app(CreateQualityCheckAction::class);
    $check = $action->execute($dto);

    expect($check)
        ->toBeInstanceOf(QualityCheck::class)
        ->number->toStartWith('QC-')
        ->status->toBe(QualityCheckStatus::Draft)
        ->company_id->toBe($company->id)
        ->product_id->toBe($product->id)
        ->source_type->toBe(get_class($picking))
        ->source_id->toBe($picking->id);
});

it('auto-generates sequential check numbers', function () {
    $company = createCompanyWithRequiredAccounts();
    $user = createUserForCompany($company);

    actingAs($user);

    $product = Product::factory()->forCompany($company)->create();
    $picking = StockPicking::factory()->forCompany($company)->create();

    $action = app(CreateQualityCheckAction::class);

    $check1 = $action->execute(new CreateQualityCheckDTO(
        companyId: $company->id,
        sourceType: get_class($picking),
        sourceId: $picking->id,
        productId: $product->id,
    ));

    $check2 = $action->execute(new CreateQualityCheckDTO(
        companyId: $company->id,
        sourceType: get_class($picking),
        sourceId: $picking->id,
        productId: $product->id,
    ));

    expect($check1->number)->toBe('QC-000001');
    expect($check2->number)->toBe('QC-000002');
});
