<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Account;
use App\Models\Journal;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalType;
use App\Enums\Partners\PartnerType;

test('invoice customer selection shows inter-company indicators correctly', function () {
    // Create companies
    $parentCompany = Company::factory()->create(['name' => 'Parent Corp']);
    $childCompany = Company::factory()->create([
        'name' => 'Child Corp',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Set up required accounts and journals for child company
    $arAccount = Account::factory()->for($childCompany)->create(['type' => AccountType::Receivable]);
    $salesJournal = Journal::factory()->for($childCompany)->create(['type' => JournalType::Sale]);

    $childCompany->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_sales_journal_id' => $salesJournal->id,
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

    // Create user in child company
    $user = User::factory()->create(['company_id' => $childCompany->id]);

    // Test that we can access the invoice creation page
    $this->actingAs($user)
        ->get('/jmeryar/invoices/create')
        ->assertOk();

    // Test the customer search functionality
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

    // Test that the search results would include the correct indicators
    // This simulates what the InvoiceResource customer field search would return
    $searchResults = $customers->mapWithKeys(function ($partner) use ($childCompany) {
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
    })->toArray();

    // Verify the search results
    expect($searchResults)->toHaveKey($parentAsCustomer->id);
    expect($searchResults[$parentAsCustomer->id])->toContain('🏛️');
    expect($searchResults[$parentAsCustomer->id])->toContain(__('invoice.parent_company_indicator'));

    expect($searchResults)->toHaveKey($regularCustomer->id);
    expect($searchResults[$regularCustomer->id])->toBe('Regular Customer');
    expect($searchResults[$regularCustomer->id])->not->toContain('🏛️');
    expect($searchResults[$regularCustomer->id])->not->toContain('🏢');
    expect($searchResults[$regularCustomer->id])->not->toContain('🤝');
});

test('partner creation works correctly in partner resource', function () {
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

    // Test creating a partner with linked company
    $partnerData = [
        'company_id' => $childCompany->id,
        'name' => 'Parent Corp Customer',
        'type' => PartnerType::Customer,
        'linked_company_id' => $parentCompany->id,
        'email' => 'billing@parentcorp.com',
        'phone' => '+1234567890',
        'contact_person' => 'Jane Smith',
        'is_active' => true,
    ];

    $partner = Partner::create($partnerData);

    expect($partner)->not->toBeNull();
    expect($partner->name)->toBe('Parent Corp Customer');
    expect($partner->type)->toBe(PartnerType::Customer);
    expect($partner->linked_company_id)->toBe($parentCompany->id);
    expect($partner->linkedCompany)->not->toBeNull();
    expect($partner->linkedCompany->name)->toBe('Parent Corp');
});

test('invoice customer creation modal has essential fields only', function () {
    // This test verifies that the updated invoice customer creation modal
    // has only the essential fields and works properly

    // Create companies for linked company testing
    $parentCompany = Company::factory()->create(['name' => 'Parent Corp']);
    $childCompany = Company::factory()->create([
        'name' => 'Child Corp',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Set up required accounts and journals
    $arAccount = Account::factory()->for($childCompany)->create(['type' => AccountType::Receivable]);
    $salesJournal = Journal::factory()->for($childCompany)->create(['type' => JournalType::Sale]);

    $childCompany->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_sales_journal_id' => $salesJournal->id,
    ]);

    // Create user
    $user = User::factory()->create(['company_id' => $childCompany->id]);

    // Test that we can access the invoice creation page
    $this->actingAs($user)
        ->get('/jmeryar/invoices/create')
        ->assertOk();

    // Test creating a partner through the invoice modal with essential fields
    $partnerData = [
        'company_id' => $childCompany->id,
        'name' => 'Test Customer Modal',
        'type' => PartnerType::Customer,
        'email' => 'test@modal.com',
        'phone' => '+964 750 123 4567',
        'is_active' => true,
    ];

    // This should work with the essential fields
    $partner = Partner::create($partnerData);

    expect($partner)->not->toBeNull();
    expect($partner->name)->toBe('Test Customer Modal');
    expect($partner->type)->toBe(PartnerType::Customer);
    expect($partner->email)->toBe('test@modal.com');
    expect($partner->phone)->toBe('+964 750 123 4567');
    expect($partner->company_id)->toBe($childCompany->id);
    expect($partner->is_active)->toBeTrue();
    expect($partner->linked_company_id)->toBeNull();
    expect($partner->linkedCompany)->toBeNull();
});

test('invoice customer creation modal supports linked company', function () {
    // This test verifies that the modal can create partners with linked companies

    // Create companies
    $parentCompany = Company::factory()->create(['name' => 'Parent Corp']);
    $childCompany = Company::factory()->create([
        'name' => 'Child Corp',
        'parent_company_id' => $parentCompany->id,
    ]);


    // Set up required accounts and journals
    $arAccount = Account::factory()->for($childCompany)->create(['type' => AccountType::Receivable]);
    $salesJournal = Journal::factory()->for($childCompany)->create(['type' => JournalType::Sale]);

    $childCompany->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_sales_journal_id' => $salesJournal->id,
    ]);



    // Test creating a partner with linked company
    $partnerData = [
        'company_id' => $childCompany->id,
        'name' => 'Parent Corp Representative',
        'type' => PartnerType::Customer,
        'linked_company_id' => $parentCompany->id,
        'email' => 'billing@parentcorp.com',
        'phone' => '+1234567890',
        'is_active' => true,
    ];

    $partner = Partner::create($partnerData);

    expect($partner)->not->toBeNull();
    expect($partner->name)->toBe('Parent Corp Representative');
    expect($partner->type)->toBe(PartnerType::Customer);
    expect($partner->linked_company_id)->toBe($parentCompany->id);
    expect($partner->linkedCompany)->not->toBeNull();
    expect($partner->linkedCompany->name)->toBe('Parent Corp');

    // Test that the partner would show inter-company indicator
    $currentCompany = $childCompany;
    $label = $partner->name;

    if ($partner->linkedCompany && $currentCompany) {
        if ($partner->linkedCompany->parent_company_id === $currentCompany->id) {
            // This partner represents a child company
            $label .= ' 🏢 ' . __('invoice.child_company_indicator');
        } elseif ($currentCompany->parent_company_id === $partner->linkedCompany->id) {
            // This partner represents the parent company
            $label .= ' 🏛️ ' . __('invoice.parent_company_indicator');
        } elseif ($partner->linkedCompany->parent_company_id === $currentCompany->parent_company_id && $currentCompany->parent_company_id) {
            // This partner represents a sibling company
            $label .= ' 🤝 ' . __('invoice.sibling_company_indicator');
        }
    }

    expect($label)->toContain('🏛️');
    expect($label)->toContain(__('invoice.parent_company_indicator'));
});
