<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function createPosRole(): Role
{
    $existing = Role::where(['name' => 'pos_cashier', 'guard_name' => 'web', 'company_id' => 1])->first();
    if ($existing) {
        return $existing;
    }

    $roleId = DB::table('roles')->insertGetId([
        'name' => 'pos_cashier',
        'guard_name' => 'web',
        'company_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Role::find($roleId);
}

function createRoleWithId(string $name, int $companyId = 1): Role
{
    $existing = Role::where(['name' => $name, 'guard_name' => 'web', 'company_id' => $companyId])->first();
    if ($existing) {
        return $existing;
    }

    $roleId = DB::table('roles')->insertGetId([
        'name' => $name,
        'guard_name' => 'web',
        'company_id' => $companyId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Role::find($roleId);
}

beforeEach(function () {
    // Isolated tests: Clear everything
    DB::table('model_has_roles')->delete();
    DB::table('model_has_permissions')->delete();
    DB::table('role_has_permissions')->delete();
    DB::table('roles')->delete();
    DB::table('permissions')->delete();
    DB::table('users')->delete();
    DB::table('companies')->delete();

    // Clear Spatie cache
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    // Company 1
    $this->company = Company::factory()->create(['id' => 1]);
    setPermissionsTeamId(1);
    $this->posRole = createPosRole();
    $this->permission = Permission::findOrCreate('access_pos_terminal', 'web');
    $this->posRole->givePermissionTo($this->permission);
});

test('pos cashier can access the pos terminal', function () {
    $user = User::factory()->create();
    $user->companies()->attach(1);
    $user->assignRole($this->posRole);

    $this->actingAs($user)
        ->get(route('pos.terminal'))
        ->assertOk();
});

test('user without any role or permission cannot access the pos terminal', function () {
    $user = User::factory()->create();
    $user->companies()->attach(1);

    $this->actingAs($user)
        ->get(route('pos.terminal'))
        ->assertForbidden();
});

test('pos only user is redirected to pos terminal when accessing the main panel', function () {
    $user = User::factory()->create();
    $user->companies()->attach(1);
    $user->assignRole($this->posRole);

    $this->actingAs($user)
        ->get('/kezi/1')
        ->assertRedirect(route('pos.terminal'));
});

test('accountant role can access the pos terminal', function () {
    $role = createRoleWithId('accountant');
    $role->givePermissionTo($this->permission);
    $user = User::factory()->create();
    $user->companies()->attach(1);
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(route('pos.terminal'))
        ->assertOk();
});

test('sales manager role can access the pos terminal', function () {
    $role = createRoleWithId('sales_manager');
    $role->givePermissionTo($this->permission);
    $user = User::factory()->create();
    $user->companies()->attach(1);
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(route('pos.terminal'))
        ->assertOk();
});

test('super admin can access the pos terminal', function () {
    $role = createRoleWithId('super_admin');
    $role->givePermissionTo($this->permission);
    $user = User::factory()->create();
    $user->companies()->attach(1);
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(route('pos.terminal'))
        ->assertOk();
});

test('user with pos cashier and another role is not confined to pos terminal', function () {
    $adminRole = createRoleWithId('admin_extra');
    $adminRole->givePermissionTo($this->permission);
    $user = User::factory()->create();
    $user->companies()->attach(1);
    $user->assignRole($this->posRole);
    $user->assignRole($adminRole);

    // Should NOT be redirected — has multiple roles
    $this->actingAs($user)
        ->get('/kezi/1')
        ->assertOk();
});

test('super admin can access both the main panel and pos terminal', function () {
    $role = createRoleWithId('super_admin');
    $role->givePermissionTo($this->permission);
    $user = User::factory()->create();
    $user->companies()->attach(1);
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(route('pos.terminal'))
        ->assertOk();
});
