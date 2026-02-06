<?php

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ListInvoices;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\ListJournalEntries;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\ListVendorBills;
use Kezi\Foundation\Actions\SetupCompanyRolesAction;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    // Seed permissions (simulating what OnboardingWizardSeeder does)
    $this->seed(\Kezi\Foundation\Database\Seeders\PermissionSeeder::class);

    // Create user (simulating registration)
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create company and attach user (simulating company registration)
    $this->company = Company::factory()->create();
    $this->company->users()->attach($this->user);

    // Run the action (simulating what RegisterCompany::handleRegistration does)
    app(SetupCompanyRolesAction::class)->execute($this->company, $this->user);

    // Set Filament context
    Filament::setTenant($this->company);
    Filament::setCurrentPanel(Filament::getPanel('kezi'));
});

test('user has super_admin role after onboarding simulation', function () {
    setPermissionsTeamId($this->company->id);
    $this->user->refresh();

    expect($this->user->hasRole('super_admin'))->toBeTrue();
});

test('super_admin can access journal entries list after onboarding', function () {
    livewire(ListJournalEntries::class)
        ->assertSuccessful();
});

test('super_admin can access invoices list after onboarding', function () {
    livewire(ListInvoices::class)
        ->assertSuccessful();
});

test('super_admin can access vendor bills list after onboarding', function () {
    livewire(ListVendorBills::class)
        ->assertSuccessful();
});

test('user without role assignment cannot access resources', function () {
    // Create a new user without role assignment
    $newUser = User::factory()->create();
    $newUser->companies()->attach($this->company);

    $this->actingAs($newUser);

    livewire(ListJournalEntries::class)
        ->assertForbidden();
});
