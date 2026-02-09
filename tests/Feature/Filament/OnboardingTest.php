<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Tenancy\RegisterCompany;
use App\Models\Company;
use App\Models\User;
use Filament\Auth\Pages\Register;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Enums\Inventory\InventoryAccountingMode;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('homepage has login and register buttons', function () {
    $this->get('/')
        ->assertStatus(200)
        ->assertSee(__('Sign in'))
        ->assertSee(__('Start Free Trial'));
});

test('new user can register and is redirected to company onboarding', function () {
    livewire(Register::class)
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'passwordConfirmation' => 'password',
        ])
        ->call('register')
        ->assertRedirect('/kezi');

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

test('onboarding wizard can be completed and seeds data', function () {
    // 1. Setup foundation data (Currencies, Roles, etc.)
    $this->seed(\Database\Seeders\OnboardingWizardSeeder::class);

    $user = User::factory()->create();
    $this->actingAs($user);

    // 2. Start Wizard
    livewire(RegisterCompany::class)
        // Step 1: Identity
        ->fillForm([
            'name' => 'Acme Corp',
            'tax_id' => '123-456',
            'address' => 'Erbil, Iraq',
        ])
        // In Filament Wizards, we usually call 'next' or similar to navigate,
        // but for RegisterTenant, the 'register' action is what saves.
        // Let's fill all steps.
        ->fillForm([
            'currency_id' => \Kezi\Foundation\Models\Currency::where('code', 'IQD')->first()->id,
            'fiscal_country' => 'IQ',
            'industry_type' => 'retail',
            'inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL->value,
            'seed_sample_data' => true,
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    // 3. Verify results
    $company = Company::where('name', 'Acme Corp')->first();
    expect($company)->not->toBeNull();
    expect($company->onboarding_completed_at)->not->toBeNull();
    expect($company->industry_type)->toBe('retail');

    // Verify seeding
    $this->assertDatabaseHas('accounts', ['company_id' => $company->id, 'code' => '110101']);
    
    // Check if account group is assigned
    $account = \Kezi\Accounting\Models\Account::where('company_id', $company->id)->where('code', '110101')->first();
    expect($account->account_group_id)->not->toBeNull();

    $this->assertDatabaseHas('journals', ['company_id' => $company->id, 'short_code' => 'BNK']);
    $this->assertDatabaseHas('partners', ['company_id' => $company->id, 'name' => 'Sample Customer']);
});
