<?php

namespace Kezi\Foundation\Actions;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates default roles for a company and optionally assigns super_admin to a user.
 */
class SetupCompanyRolesAction
{
    /**
     * Execute the action.
     *
     * @param  Company  $company  The company to create roles for
     * @param  User|null  $adminUser  Optional user to assign super_admin role
     */
    public function execute(Company $company, ?User $adminUser = null): void
    {
        // Reset cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Set team context for Spatie Permission
        setPermissionsTeamId($company->id);

        // Create super_admin role with all permissions
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'company_id' => $company->id,
        ]);
        $superAdmin->syncPermissions(Permission::all());

        // Create accountant role
        $accountant = Role::firstOrCreate([
            'name' => 'accountant',
            'company_id' => $company->id,
        ]);
        $accountant->syncPermissions([
            'view_any_journal_entry', 'view_journal_entry', 'create_journal_entry', 'update_journal_entry', 'post_journal_entry', 'reverse_journal_entry',
            'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice', 'confirm_invoice',
            'view_any_vendor_bill', 'view_vendor_bill', 'create_vendor_bill', 'update_vendor_bill', 'confirm_vendor_bill',
            'view_financial_reports',
            'view_any_partner', 'view_partner', 'create_partner', 'update_partner',
        ]);

        // Create inventory_manager role
        $inventoryManager = Role::firstOrCreate([
            'name' => 'inventory_manager',
            'company_id' => $company->id,
        ]);
        $inventoryManager->syncPermissions([
            'view_any_product', 'view_product', 'create_product', 'update_product',
            'view_any_stock_move', 'view_stock_move', 'confirm_stock_move',
            'validate_stock_picking',
            'view_any_warehouse', 'view_warehouse', 'create_warehouse', 'update_warehouse',
        ]);

        // Create sales_manager role
        $salesManager = Role::firstOrCreate([
            'name' => 'sales_manager',
            'company_id' => $company->id,
        ]);
        $salesManager->syncPermissions([
            'view_any_quote', 'view_quote', 'create_quote', 'update_quote',
            'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice', 'confirm_invoice', 'cancel_invoice',
            'view_any_partner', 'view_partner', 'create_partner', 'update_partner',
        ]);

        // Create employee role (basic access)
        $employee = Role::firstOrCreate([
            'name' => 'employee',
            'company_id' => $company->id,
        ]);
        $employee->syncPermissions([
            'view_any_product', 'view_product',
        ]);

        // Assign super_admin to the provided user if specified
        if ($adminUser) {
            $this->assignSuperAdmin($superAdmin, $adminUser, $company);
        }
    }

    /**
     * Assign super_admin role to a user for a company.
     */
    protected function assignSuperAdmin(Role $superAdmin, User $user, Company $company): void
    {
        // Check if already assigned to avoid duplicates
        $exists = DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('role_id', $superAdmin->id)
            ->where('company_id', $company->id)
            ->exists();

        if (! $exists) {
            DB::table('model_has_roles')->insert([
                'role_id' => $superAdmin->id,
                'model_type' => get_class($user),
                'model_id' => $user->id,
                'company_id' => $company->id,
            ]);
        }

        // Refresh the user to clear cached relationships
        $user->refresh();
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }
}
