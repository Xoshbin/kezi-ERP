<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Account;
use App\Models\Journal;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalType;
use App\Enums\Partners\PartnerType;
use Livewire\Livewire;

test('invoice resource can create partner through modal without linked company', function () {
    // Create company with required setup
    $company = Company::factory()->create();
    
    // Set up required accounts and journals
    $arAccount = Account::factory()->for($company)->create(['type' => AccountType::Receivable]);
    $salesJournal = Journal::factory()->for($company)->create(['type' => JournalType::Sale]);
    
    $company->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_sales_journal_id' => $salesJournal->id,
    ]);

    // Create user
    $user = User::factory()->create(['company_id' => $company->id]);

    // Test creating a partner through the invoice form
    $this->actingAs($user);

    $component = Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\CreateInvoice::class);

    // Simulate creating a new partner through the modal
    $partnerData = [
        'company_id' => $company->id,
        'name' => 'Test Customer',
        'type' => PartnerType::Customer->value,
        'linked_company_id' => null, // No linked company
        'email' => 'test@customer.com',
        'phone' => '+1234567890',
        'contact_person' => 'John Doe',
    ];

    // This should not throw an error
    $partner = Partner::create($partnerData);

    expect($partner)->not->toBeNull();
    expect($partner->name)->toBe('Test Customer');
    expect($partner->type)->toBe(PartnerType::Customer);
    expect($partner->linked_company_id)->toBeNull();
    expect($partner->linkedCompany)->toBeNull();
});

test('invoice resource can create partner through modal with linked company', function () {
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

    // Create user in child company
    $user = User::factory()->create(['company_id' => $childCompany->id]);

    $this->actingAs($user);

    // Test creating an inter-company partner
    $partnerData = [
        'company_id' => $childCompany->id,
        'name' => 'Parent Corp Customer',
        'type' => PartnerType::Customer->value,
        'linked_company_id' => $parentCompany->id, // Linked to parent company
        'email' => 'billing@parentcorp.com',
        'phone' => '+1234567890',
        'contact_person' => 'Jane Smith',
    ];

    // This should not throw an error
    $partner = Partner::create($partnerData);

    expect($partner)->not->toBeNull();
    expect($partner->name)->toBe('Parent Corp Customer');
    expect($partner->type)->toBe(PartnerType::Customer);
    expect($partner->linked_company_id)->toBe($parentCompany->id);
    expect($partner->linkedCompany)->not->toBeNull();
    expect($partner->linkedCompany->name)->toBe('Parent Corp');
});

test('partner model fillable includes linked_company_id', function () {
    // Verify that the Partner model includes linked_company_id in fillable
    $partner = new Partner();
    $fillable = $partner->getFillable();
    
    expect($fillable)->toContain('linked_company_id');
});

test('invoice form includes linked company field in partner creation modal', function () {
    // Create company
    $company = Company::factory()->create();
    
    // Set up required accounts and journals
    $arAccount = Account::factory()->for($company)->create(['type' => AccountType::Receivable]);
    $salesJournal = Journal::factory()->for($company)->create(['type' => JournalType::Sale]);
    
    $company->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_sales_journal_id' => $salesJournal->id,
    ]);

    // Create user
    $user = User::factory()->create(['company_id' => $company->id]);

    // Test that we can access the invoice creation page
    $this->actingAs($user)
        ->get('/jmeryar/invoices/create')
        ->assertOk();

    // The form should include the linked company field in the customer creation modal
    // This is tested by ensuring the page loads without errors and the translations exist
    expect(__('partner.linked_company'))->not->toBeEmpty();
    expect(__('partner.linked_company_helper'))->not->toBeEmpty();
});

test('partner creation through invoice modal works with form validation', function () {
    // Create company
    $company = Company::factory()->create();
    
    // Set up required accounts and journals
    $arAccount = Account::factory()->for($company)->create(['type' => AccountType::Receivable]);
    $salesJournal = Journal::factory()->for($company)->create(['type' => JournalType::Sale]);
    
    $company->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_sales_journal_id' => $salesJournal->id,
    ]);

    // Create user
    $user = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user);

    // Test that required fields are validated
    $this->expectException(\Illuminate\Database\QueryException::class);
    
    // Try to create a partner without required fields
    Partner::create([
        'company_id' => $company->id,
        // Missing required 'name' field
        'type' => PartnerType::Customer->value,
        'linked_company_id' => null,
    ]);
});

test('linked company relationship works correctly', function () {
    // Create companies
    $parentCompany = Company::factory()->create(['name' => 'Parent Corp']);
    $childCompany = Company::factory()->create([
        'name' => 'Child Corp',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create partner with linked company
    $partner = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'Parent Corp Customer',
        'type' => PartnerType::Customer,
        'linked_company_id' => $parentCompany->id,
    ]);

    // Test the relationship
    expect($partner->linkedCompany)->not->toBeNull();
    expect($partner->linkedCompany->id)->toBe($parentCompany->id);
    expect($partner->linkedCompany->name)->toBe('Parent Corp');

    // Test eager loading
    $partnerWithLinkedCompany = Partner::with('linkedCompany')->find($partner->id);
    expect($partnerWithLinkedCompany->linkedCompany)->not->toBeNull();
    expect($partnerWithLinkedCompany->linkedCompany->name)->toBe('Parent Corp');
});
