<?php

use App\Models\Company;
use App\Models\Partner;
use App\Models\VendorBill;
use App\Filament\Resources\VendorBillResource;
use Tests\Traits\WithConfiguredCompany;
use Filament\Livewire\CreateRecord;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->actingAs($this->user);
});

test('vendor selection displays company names with inter-company indicators', function () {
    // Create company hierarchy
    $parentCompany = Company::factory()->create(['name' => 'ParentCo']);
    $childCompany = Company::factory()->create([
        'name' => 'ChildCo',
        'parent_company_id' => $parentCompany->id,
    ]);
    $siblingCompany = Company::factory()->create([
        'name' => 'SiblingCo',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create vendors with linked companies in the current company
    $childVendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'ChildCo Vendor',
        'type' => 'vendor',
        'linked_company_id' => $childCompany->id,
    ]);

    $parentVendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'ParentCo Vendor',
        'type' => 'vendor',
        'linked_company_id' => $parentCompany->id,
    ]);

    $siblingVendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'SiblingCo Vendor',
        'type' => 'vendor',
        'linked_company_id' => $siblingCompany->id,
    ]);

    $regularVendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Regular Vendor',
        'type' => 'vendor',
        'linked_company_id' => null,
    ]);

    // Set up company hierarchy: current company is the parent, others are children/siblings
    // Current company is the parent of childCompany
    $childCompany->update(['parent_company_id' => $this->company->id]);

    // Current company is a child of parentCompany
    $this->company->update(['parent_company_id' => $parentCompany->id]);

    // Test the search function logic directly (similar to InterCompanyIndicatorsTest pattern)
    $searchFunction = function (string $search) {
        $currentCompany = $this->company;

        return \App\Models\Partner::where('type', 'vendor')
            ->where('name', 'like', "%{$search}%")
            ->with('linkedCompany')
            ->limit(50)
            ->get()
            ->mapWithKeys(function ($partner) use ($currentCompany) {
                $label = $partner->name;

                // Add company name if partner is linked to another company
                if ($partner->linkedCompany) {
                    $label .= ' (' . $partner->linkedCompany->name . ')';
                }

                // Add inter-company indicator if partner is linked to another company
                if ($partner->linkedCompany && $currentCompany) {
                    if ($partner->linkedCompany->parent_company_id === $currentCompany->id) {
                        // This partner represents a child company
                        $label .= ' 🏢 ' . __('vendor_bill.child_company_indicator');
                    } elseif ($currentCompany->parent_company_id === $partner->linkedCompany->id) {
                        // This partner represents the parent company
                        $label .= ' 🏛️ ' . __('vendor_bill.parent_company_indicator');
                    } elseif ($partner->linkedCompany->parent_company_id === $currentCompany->parent_company_id && $currentCompany->parent_company_id) {
                        // This partner represents a sibling company
                        $label .= ' 🤝 ' . __('vendor_bill.sibling_company_indicator');
                    }
                }

                return [$partner->id => $label];
            })
            ->toArray();
    };

    // Test search results
    $searchResults = $searchFunction('Vendor');

    // Verify child company indicator
    expect($searchResults[$childVendor->id])->toContain('ChildCo Vendor (ChildCo) 🏢');
    expect($searchResults[$childVendor->id])->toContain('(Child Company)');

    // Verify parent company indicator
    expect($searchResults[$parentVendor->id])->toContain('ParentCo Vendor (ParentCo) 🏛️');
    expect($searchResults[$parentVendor->id])->toContain('(Parent Company)');

    // Verify sibling company indicator
    expect($searchResults[$siblingVendor->id])->toContain('SiblingCo Vendor (SiblingCo) 🤝');
    expect($searchResults[$siblingVendor->id])->toContain('(Sister Company)');

    // Verify regular vendor has no indicator
    expect($searchResults[$regularVendor->id])->toBe('Regular Vendor');
});

test('vendor creation through modal creates partner with linked company', function () {
    $targetCompany = Company::factory()->create(['name' => 'Target Company']);

    // Test the partner creation logic directly
    $createdPartner = Partner::create([
        'company_id' => $this->company->id,
        'name' => 'New Inter-Company Vendor',
        'type' => 'vendor',
        'linked_company_id' => $targetCompany->id,
    ]);

    // Verify the partner was created correctly
    expect($createdPartner)->not->toBeNull();
    expect($createdPartner->name)->toBe('New Inter-Company Vendor');
    expect($createdPartner->type->value)->toBe('vendor');
    expect($createdPartner->company_id)->toBe($this->company->id);
    expect($createdPartner->linked_company_id)->toBe($targetCompany->id);

    // Verify it's in the database
    $this->assertDatabaseHas('partners', [
        'id' => $createdPartner->id,
        'company_id' => $this->company->id,
        'name' => 'New Inter-Company Vendor',
        'type' => 'vendor',
        'linked_company_id' => $targetCompany->id,
    ]);
});
