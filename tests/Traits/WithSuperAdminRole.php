<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait WithSuperAdminRole
{
    /**
     * Create and assign super_admin role for the given company.
     * Since roles are now company-specific (company_id is NOT NULL),
     * we need to ensure the super_admin role exists for each test company.
     */
    protected function assignSuperAdminRole(\App\Models\User $user, \App\Models\Company $company): void
    {
        // Ensure team context is set
        setPermissionsTeamId($company->id);

        // Create super_admin role for this company if it doesn't exist
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'company_id' => $company->id,
        ]);

        // Grant all permissions if it was just created
        if ($superAdminRole->wasRecentlyCreated) {
            $superAdminRole->givePermissionTo(Permission::all());
        }

        // Assign the role to the user
        $user->assignRole($superAdminRole);
    }
}
