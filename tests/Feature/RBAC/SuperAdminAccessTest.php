<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ListInvoices;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\ListVendorBills;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Reset permission cache
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    // Seed roles and permissions
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Create company
    $this->company = \App\Models\Company::factory()->create();

    // Create user and attach to company
    $this->user = User::factory()->create(['email' => 'superadmin@test.com']);
    $this->user->companies()->attach($this->company);

    // Set team context BEFORE assigning roles (required for Spatie Permission with teams)
    setPermissionsTeamId($this->company->id);

    // Create super_admin role for this company (since roles are now company-specific)
    $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate([
        'name' => 'super_admin',
        'company_id' => $this->company->id,
    ]);

    // Grant all permissions if it was just created
    if ($superAdminRole->wasRecentlyCreated) {
        $superAdminRole->givePermissionTo(\Spatie\Permission\Models\Permission::all());
    }

    // Assign super_admin role
    $this->user->assignRole($superAdminRole);

    // Reload to clear cached relationships
    $this->user->refresh();
    $this->user->unsetRelation('roles');
    $this->user->unsetRelation('permissions');

    // Act as the user
    $this->actingAs($this->user);

    // Set Filament context
    Filament::setTenant($this->company);
    Filament::setCurrentPanel(Filament::getPanel('jmeryar'));
});

test('super admin has correct role after team context is set', function () {
    // Verify the team context is working
    expect($this->user->hasRole('super_admin'))->toBeTrue();
});

test('super admin can access invoice list page', function () {
    livewire(ListInvoices::class)
        ->assertSuccessful();
});

test('super admin can access vendor bills list page', function () {
    livewire(ListVendorBills::class)
        ->assertSuccessful();
});

test('user without super_admin role cannot access invoice list', function () {
    // Create a new user without any roles
    $regularUser = User::factory()->create(['email' => 'regular@test.com']);
    $regularUser->companies()->attach($this->company);

    $this->actingAs($regularUser);

    livewire(ListInvoices::class)
        ->assertForbidden();
});

test('user without super_admin role cannot access vendor bills list', function () {
    // Create a new user without any roles
    $regularUser = User::factory()->create(['email' => 'regular2@test.com']);
    $regularUser->companies()->attach($this->company);

    $this->actingAs($regularUser);

    livewire(ListVendorBills::class)
        ->assertForbidden();
});
