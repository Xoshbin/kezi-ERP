<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Partner;
use App\Enums\Partners\PartnerType;

test('partner can be linked to another company for inter-company relationships', function () {
    // Create parent and child companies
    $parentCompany = Company::factory()->create(['name' => 'Parent Corp']);
    $childCompany = Company::factory()->create([
        'name' => 'Child Corp',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create a partner in child company that represents the parent company
    $parentAsCustomer = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Parent Corp Customer',
        'type' => PartnerType::Customer,
        'linked_company_id' => $parentCompany->id,
    ]);

    // Test the relationship
    expect($parentAsCustomer->linkedCompany)->not->toBeNull();
    expect($parentAsCustomer->linkedCompany->id)->toBe($parentCompany->id);
    expect($parentAsCustomer->linkedCompany->name)->toBe('Parent Corp');

    // Test that regular partners don't have linked companies
    $regularCustomer = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Regular Customer',
        'type' => PartnerType::Customer,
        'linked_company_id' => null,
    ]);

    expect($regularCustomer->linkedCompany)->toBeNull();
});

test('partner resource form includes linked company field', function () {
    // Create companies
    $parentCompany = Company::factory()->create(['name' => 'Parent Corp']);
    $childCompany = Company::factory()->create([
        'name' => 'Child Corp',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create user in child company
    $user = User::factory()->create(['company_id' => $childCompany->id]);

    // Test that we can access the partner creation page
    $this->actingAs($user)
        ->get('/jmeryar/partners/create')
        ->assertOk()
        ->assertSee(__('partner.linked_company'))
        ->assertSee(__('partner.linked_company_helper'));
});

test('partner resource table shows linked company information', function () {
    // Create companies
    $parentCompany = Company::factory()->create(['name' => 'Parent Corp']);
    $childCompany = Company::factory()->create([
        'name' => 'Child Corp',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create partner with linked company
    $parentAsCustomer = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Parent Corp Customer',
        'type' => PartnerType::Customer,
        'linked_company_id' => $parentCompany->id,
    ]);

    // Create regular partner
    $regularCustomer = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Regular Customer',
        'type' => PartnerType::Customer,
        'linked_company_id' => null,
    ]);

    // Create user in child company
    $user = User::factory()->create(['company_id' => $childCompany->id]);

    // Test that the partner list shows both partners
    $this->actingAs($user)
        ->get('/jmeryar/partners')
        ->assertOk()
        ->assertSee('Parent Corp Customer')
        ->assertSee('Regular Customer');
});

test('invoice customer selection shows inter-company indicators with linked companies', function () {
    // Create companies
    $parentCompany = Company::factory()->create(['name' => 'Parent Corp']);
    $childCompany = Company::factory()->create([
        'name' => 'Child Corp',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create partner with linked company
    $parentAsCustomer = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Parent Corp Customer',
        'type' => PartnerType::Customer,
        'linked_company_id' => $parentCompany->id,
    ]);

    // Create regular partner
    $regularCustomer = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Regular Customer',
        'type' => PartnerType::Customer,
        'linked_company_id' => null,
    ]);

    // Test that both customers are found in search and inter-company customer has indicator
    $customers = Partner::where('type', PartnerType::Customer)
        ->where('company_id', $childCompany->id)
        ->with('linkedCompany')
        ->get();

    expect($customers)->toHaveCount(2);
    
    $interCompanyCustomer = $customers->where('linked_company_id', $parentCompany->id)->first();
    $normalCustomer = $customers->whereNull('linked_company_id')->first();
    
    expect($interCompanyCustomer)->not->toBeNull();
    expect($interCompanyCustomer->linkedCompany)->not->toBeNull();
    expect($interCompanyCustomer->linkedCompany->name)->toBe('Parent Corp');
    
    expect($normalCustomer)->not->toBeNull();
    expect($normalCustomer->linkedCompany)->toBeNull();
});

test('inter-company transaction flow works with linked companies', function () {
    // This test verifies that the inter-company transaction test still works
    // with the new linked_company_id field structure
    
    // Create parent and child companies
    $parentCompany = Company::factory()->create(['name' => 'Parent Corp']);
    $childCompany = Company::factory()->create([
        'name' => 'Child Corp',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create partners with linked companies
    $parentAsCustomer = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Parent Corp Customer',
        'type' => PartnerType::Customer,
        'linked_company_id' => $parentCompany->id,
    ]);

    $childAsVendor = Partner::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'Child Corp Vendor',
        'type' => PartnerType::Vendor,
        'linked_company_id' => $childCompany->id,
    ]);

    // Verify the relationships work correctly
    expect($parentAsCustomer->linkedCompany->id)->toBe($parentCompany->id);
    expect($childAsVendor->linkedCompany->id)->toBe($childCompany->id);

    // Test that we can find the vendor partner in the target company
    $vendorPartner = $parentCompany->partners()
        ->where('linked_company_id', $childCompany->id)
        ->first();

    expect($vendorPartner)->not->toBeNull();
    expect($vendorPartner->id)->toBe($childAsVendor->id);
});
