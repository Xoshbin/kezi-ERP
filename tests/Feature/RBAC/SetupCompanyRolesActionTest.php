<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Actions\SetupCompanyRolesAction;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(\Kezi\Foundation\Database\Seeders\PermissionSeeder::class);
});

test('creates super_admin role for company', function () {
    $company = Company::factory()->create();

    app(SetupCompanyRolesAction::class)->execute($company);

    expect(Role::where('name', 'super_admin')->where('company_id', $company->id)->exists())
        ->toBeTrue();
});

test('super_admin role has all permissions', function () {
    $company = Company::factory()->create();

    app(SetupCompanyRolesAction::class)->execute($company);

    $superAdmin = Role::where('name', 'super_admin')->where('company_id', $company->id)->first();
    $allPermissions = Permission::all();

    expect($superAdmin->permissions->count())->toBe($allPermissions->count());
});

test('creates predefined roles for company', function () {
    $company = Company::factory()->create();

    app(SetupCompanyRolesAction::class)->execute($company);

    $expectedRoles = ['super_admin', 'accountant', 'inventory_manager', 'sales_manager', 'employee'];

    foreach ($expectedRoles as $roleName) {
        expect(Role::where('name', $roleName)->where('company_id', $company->id)->exists())
            ->toBeTrue("Role {$roleName} should exist for company");
    }
});

test('assigns super_admin role to provided user', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);

    // Set team context
    setPermissionsTeamId($company->id);

    app(SetupCompanyRolesAction::class)->execute($company, $user);

    // Refresh user to get updated roles
    $user->refresh();

    expect($user->hasRole('super_admin'))->toBeTrue();
});

test('action is idempotent - can be called multiple times', function () {
    $company = Company::factory()->create();

    // Call twice
    app(SetupCompanyRolesAction::class)->execute($company);
    app(SetupCompanyRolesAction::class)->execute($company);

    // Should only have one super_admin role for this company
    $roleCount = Role::where('name', 'super_admin')->where('company_id', $company->id)->count();

    expect($roleCount)->toBe(1);
});

test('accountant role has correct permissions', function () {
    $company = Company::factory()->create();

    app(SetupCompanyRolesAction::class)->execute($company);

    $accountant = Role::where('name', 'accountant')->where('company_id', $company->id)->first();

    expect($accountant->hasPermissionTo('view_any_journal_entry'))->toBeTrue();
    expect($accountant->hasPermissionTo('create_invoice'))->toBeTrue();
    expect($accountant->hasPermissionTo('view_financial_reports'))->toBeTrue();
});

test('employee role has minimal permissions', function () {
    $company = Company::factory()->create();

    app(SetupCompanyRolesAction::class)->execute($company);

    $employee = Role::where('name', 'employee')->where('company_id', $company->id)->first();

    expect($employee->hasPermissionTo('view_any_product'))->toBeTrue();
    expect($employee->hasPermissionTo('view_product'))->toBeTrue();
    expect($employee->permissions->count())->toBe(2);
});
