<?php

use App\Models\Company;
use App\Models\User;
use Jmeryar\Inventory\Models\StockPicking;
use Jmeryar\Product\Models\Product;
use Jmeryar\QualityControl\Actions\CreateQualityCheckAction;
use Jmeryar\QualityControl\DataTransferObjects\CreateQualityCheckDTO;
use Jmeryar\QualityControl\Enums\QualityCheckStatus;
use Jmeryar\QualityControl\Models\QualityCheck;

it('creates a quality check from DTO', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);
    $picking = StockPicking::factory()->create(['company_id' => $company->id]);

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
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);
    $picking = StockPicking::factory()->create(['company_id' => $company->id]);

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

it('sets is_blocking from DTO', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->create(['company_id' => $company->id]);
    $picking = StockPicking::factory()->create(['company_id' => $company->id]);

    $dto = new CreateQualityCheckDTO(
        companyId: $company->id,
        sourceType: get_class($picking),
        sourceId: $picking->id,
        productId: $product->id,
        isBlocking: true
    );

    $action = app(CreateQualityCheckAction::class);
    $check = $action->execute($dto);

    expect($check->is_blocking)->toBeTrue();
});
