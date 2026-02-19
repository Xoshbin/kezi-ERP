<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Handle Role (Role has company_id)
        $role = \Illuminate\Support\Facades\DB::table('roles')
            ->where(['name' => 'pos_cashier', 'guard_name' => 'web', 'company_id' => 1])
            ->first();

        if (! $role) {
            $roleId = \Illuminate\Support\Facades\DB::table('roles')->insertGetId([
                'name' => 'pos_cashier',
                'guard_name' => 'web',
                'company_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $roleId = $role->id;
        }

        // Handle Permission (Permission DOES NOT have company_id)
        $permission = \Illuminate\Support\Facades\DB::table('permissions')
            ->where(['name' => 'access_pos_terminal', 'guard_name' => 'web'])
            ->first();

        if (! $permission) {
            $permissionId = \Illuminate\Support\Facades\DB::table('permissions')->insertGetId([
                'name' => 'access_pos_terminal',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $permissionId = $permission->id;
        }

        // Link Role and Permission
        \Illuminate\Support\Facades\DB::table('role_has_permissions')->updateOrInsert([
            'permission_id' => $permissionId,
            'role_id' => $roleId,
        ]);

        // Also grant access_pos_terminal to accounting, sales, and admin roles
        $rolesWithPosAccess = ['accountant', 'sales_manager', 'super_admin'];

        foreach ($rolesWithPosAccess as $roleName) {
            $existingRole = \Illuminate\Support\Facades\DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($existingRole) {
                \Illuminate\Support\Facades\DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $existingRole->id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = \Illuminate\Support\Facades\DB::table('roles')->where(['name' => 'pos_cashier', 'guard_name' => 'web'])->first();
        if ($role) {
            \Illuminate\Support\Facades\DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
            \Illuminate\Support\Facades\DB::table('roles')->where('id', $role->id)->delete();
        }

        $permission = \Illuminate\Support\Facades\DB::table('permissions')->where(['name' => 'access_pos_terminal', 'guard_name' => 'web'])->first();
        if ($permission) {
            \Illuminate\Support\Facades\DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};
