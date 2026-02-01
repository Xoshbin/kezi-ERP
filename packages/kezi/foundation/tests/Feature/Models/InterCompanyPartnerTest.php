<?php

namespace Kezi\Foundation\Tests\Feature\Models;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Consolidation\ConsolidationMethod;
use Kezi\Foundation\Models\Partner;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('partner can be linked to another company', function () {
    $parentCompany = $this->company;
    $subsidiaryCompany = Company::factory()->create([
        'parent_company_id' => $parentCompany->id,
        'name' => 'Subsidiary Inc',
    ]);

    // Create a partner in the parent company that represents the subsidiary
    $interCompanyPartner = Partner::factory()->for($parentCompany)->create([
        'name' => 'Subsidiary Partner',
        'linked_company_id' => $subsidiaryCompany->id,
    ]);

    expect($interCompanyPartner->linked_company_id)->toBe($subsidiaryCompany->id);
    expect($interCompanyPartner->linkedCompany)->not->toBeNull();
    expect($interCompanyPartner->linkedCompany->id)->toBe($subsidiaryCompany->id);
    expect($interCompanyPartner->isInterCompanyPartner())->toBeTrue();

    // Regular partner
    $regularPartner = Partner::factory()->for($parentCompany)->create();
    expect($regularPartner->isInterCompanyPartner())->toBeFalse();
});

test('company has consolidation method', function () {
    $company = Company::factory()->create([
        'consolidation_method' => ConsolidationMethod::Proportional,
    ]);

    expect($company->consolidation_method)->toBe(ConsolidationMethod::Proportional);

    // Test default
    $defaultCompany = Company::factory()->create();
    // Assuming default is Full, but we haven't implemented it yet.
    // This part might fail if default isn't set in factory, but that's what we want to test/implement.
});
