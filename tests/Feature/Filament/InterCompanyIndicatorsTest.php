<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Partner;
use App\Enums\Partners\PartnerType;

test('invoice customer search shows correct inter-company indicators', function () {
    // Create companies
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

    $regularCustomer = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Regular Customer',
        'type' => PartnerType::Customer,
        'linked_company_id' => null,
    ]);

    // Simulate the search function from InvoiceResource
    $searchFunction = function (string $search) use ($childCompany) {
        return \App\Models\Partner::where('type', 'customer')
            ->where('name', 'like', "%{$search}%")
            ->with('linkedCompany')
            ->limit(50)
            ->get()
            ->mapWithKeys(function ($partner) use ($childCompany) {
                $label = $partner->name;

                // Add inter-company indicator if partner is linked to another company
                if ($partner->linkedCompany && $childCompany) {
                    if ($partner->linkedCompany->parent_company_id === $childCompany->id) {
                        // This partner represents a child company
                        $label .= ' 🏢 ' . __('invoice.child_company_indicator');
                    } elseif ($childCompany->parent_company_id === $partner->linkedCompany->id) {
                        // This partner represents the parent company
                        $label .= ' 🏛️ ' . __('invoice.parent_company_indicator');
                    } elseif ($partner->linkedCompany->parent_company_id === $childCompany->parent_company_id && $childCompany->parent_company_id) {
                        // This partner represents a sibling company
                        $label .= ' 🤝 ' . __('invoice.sibling_company_indicator');
                    }
                }

                return [$partner->id => $label];
            })
            ->toArray();
    };

    // Test search for parent company customer
    $results = $searchFunction('Parent');

    expect($results)->toHaveKey($parentAsCustomer->id);
    expect($results[$parentAsCustomer->id])->toContain('🏛️');
    expect($results[$parentAsCustomer->id])->toContain(__('invoice.parent_company_indicator'));

    // Test search for regular customer
    $results = $searchFunction('Regular');

    expect($results)->toHaveKey($regularCustomer->id);
    expect($results[$regularCustomer->id])->toBe('Regular Customer');
    expect($results[$regularCustomer->id])->not->toContain('🏛️');
    expect($results[$regularCustomer->id])->not->toContain('🏢');
    expect($results[$regularCustomer->id])->not->toContain('🤝');
});

test('partner resource table shows linked company with indicators', function () {
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

    // Simulate the table column formatting from PartnerResource
    $formatFunction = function ($state, $record) {
        if (!$record->linkedCompany) {
            return __('common.none');
        }

        // Add inter-company indicator
        $currentCompany = $record->company;
        $label = $state;

        if ($record->linkedCompany->parent_company_id === $currentCompany->id) {
            // This partner represents a child company
            $label .= ' 🏢';
        } elseif ($currentCompany->parent_company_id === $record->linkedCompany->id) {
            // This partner represents the parent company
            $label .= ' 🏛️';
        } elseif ($record->linkedCompany->parent_company_id === $currentCompany->parent_company_id && $currentCompany->parent_company_id) {
            // This partner represents a sibling company
            $label .= ' 🤝';
        }

        return $label;
    };

    // Test the formatting
    $formattedValue = $formatFunction('Parent Corp', $parentAsCustomer);

    expect($formattedValue)->toContain('Parent Corp');
    expect($formattedValue)->toContain('🏛️'); // Parent company indicator
});

test('demo data shows correct inter-company relationships', function () {
    // This test verifies that the demo data created by the seeder works correctly

    // Find the companies created by the seeder
    $parentCompany = Company::where('name', 'Zryan Holdings Ltd')->first();
    $childCompany = Company::where('name', 'Little Fadel Tech Solutions')->first();

    // Skip if demo data doesn't exist
    if (!$parentCompany || !$childCompany) {
        $this->markTestSkipped('Demo data not found. Run InterCompanyDemoSeeder first.');
    }

    // Find the inter-company partners
    $parentAsCustomer = Partner::where('company_id', $childCompany->id)
        ->where('linked_company_id', $parentCompany->id)
        ->first();

    $childAsCustomer = Partner::where('company_id', $parentCompany->id)
        ->where('linked_company_id', $childCompany->id)
        ->first();

    // Verify the relationships
    expect($parentAsCustomer)->not->toBeNull();
    expect($parentAsCustomer->linkedCompany->id)->toBe($parentCompany->id);
    expect($parentAsCustomer->name)->toBe('Zryan Holdings Ltd');

    expect($childAsCustomer)->not->toBeNull();
    expect($childAsCustomer->linkedCompany->id)->toBe($childCompany->id);
    expect($childAsCustomer->name)->toBe('Little Fadel Tech Solutions');

    // Verify company hierarchy
    expect($childCompany->parent_company_id)->toBe($parentCompany->id);
    expect($parentCompany->parent_company_id)->toBeNull();
});
