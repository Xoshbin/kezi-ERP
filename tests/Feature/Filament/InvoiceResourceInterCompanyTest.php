<?php

use App\Models\Company;
use App\Models\Partner;

test('partner model correctly identifies inter-company relationships', function () {
    // Create parent and child companies
    $parentCompany = Company::factory()->create(['name' => 'Parent Corp']);
    $childCompany = Company::factory()->create([
        'name' => 'Child Corp',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create partners representing inter-company relationships
    $parentAsCustomer = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Parent Corp Customer',
        'type' => 'customer',
        'linked_company_id' => $parentCompany->id,
    ]);

    $childAsCustomer = Partner::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'Child Corp Customer',
        'type' => 'customer',
        'linked_company_id' => $childCompany->id,
    ]);

    // Create regular customer (no inter-company relationship)
    $regularCustomer = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Regular Customer',
        'type' => 'customer',
        'linked_company_id' => null,
    ]);

    // Test that partners have correct linked company relationships
    expect($parentAsCustomer->linkedCompany)->not->toBeNull();
    expect($parentAsCustomer->linkedCompany->id)->toBe($parentCompany->id);

    expect($childAsCustomer->linkedCompany)->not->toBeNull();
    expect($childAsCustomer->linkedCompany->id)->toBe($childCompany->id);

    expect($regularCustomer->linkedCompany)->toBeNull();

    // Test company hierarchy
    expect($childCompany->parent_company_id)->toBe($parentCompany->id);
    expect($parentCompany->parent_company_id)->toBeNull();
});

test('invoice resource customer search includes inter-company partners', function () {
    // Create parent and child companies
    $parentCompany = Company::factory()->create(['name' => 'Parent Corp']);
    $childCompany = Company::factory()->create([
        'name' => 'Child Corp',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create partner representing parent company in child's books
    Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Parent Corp Customer',
        'type' => 'customer',
        'linked_company_id' => $parentCompany->id,
    ]);

    // Create regular customer
    Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Regular Customer',
        'type' => 'customer',
        'linked_company_id' => null,
    ]);

    // Test that both customers are found in search
    $customers = Partner::where('type', 'customer')
        ->where('company_id', $childCompany->id)
        ->with('linkedCompany')
        ->get();

    expect($customers)->toHaveCount(2);

    $interCompanyCustomer = $customers->where('linked_company_id', $parentCompany->id)->first();
    $normalCustomer = $customers->whereNull('linked_company_id')->first();

    expect($interCompanyCustomer)->not->toBeNull();
    expect($interCompanyCustomer->linkedCompany)->not->toBeNull();
    expect($normalCustomer)->not->toBeNull();
    expect($normalCustomer->linkedCompany)->toBeNull();
});
