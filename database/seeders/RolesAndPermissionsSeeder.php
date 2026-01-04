<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- 1. Create Permissions (Global) ---
        // Permissions are defined globally in the system (code-level logic).
        $permissionsByGroup = [
            'Accounting' => [
                'journal_entry' => ['view_any', 'view', 'create', 'update', 'delete', 'reverse'],
                // specific
                'post_journal_entry',
            ],
            'Sales' => [
                'invoice' => ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'],
                'quote' => ['view_any', 'view', 'create', 'update', 'delete'],
                // specific
                'confirm_invoice',
                'cancel_invoice',
            ],
            'Purchase' => [
                'vendor_bill' => ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'],
                'purchase_order' => ['view_any', 'view', 'create', 'update', 'delete'],
                // specific
                'confirm_vendor_bill',
            ],
            'Inventory' => [
                'product' => ['view_any', 'view', 'create', 'update', 'delete'],
                'stock_move' => ['view_any', 'view'],
                'warehouse' => ['view_any', 'view', 'create', 'update', 'delete'],
                // specific
                'confirm_stock_move',
                'validate_stock_picking',
            ],
            'HR' => [
                'employee' => ['view_any', 'view', 'create', 'update', 'delete'],
                'department' => ['view_any', 'view', 'create', 'update', 'delete'],
            ],
            'Partners' => [
                'partner' => ['view_any', 'view', 'create', 'update', 'delete'],
            ],
            'System' => [
                'user' => ['view_any', 'view', 'create', 'update', 'delete'],
                'role' => ['view_any', 'view', 'create', 'update', 'delete'],
                'view_financial_reports',
            ],
        ];

        foreach ($permissionsByGroup as $group => $definitions) {
            foreach ($definitions as $key => $actions) {
                if (is_numeric($key)) {
                    // It's a single permission string like 'post_journal_entry'
                    Permission::firstOrCreate(['name' => $actions]);
                } else {
                    // It's a resource with CRUD actions
                    $resource = $key;
                    foreach ($actions as $action) {
                        Permission::firstOrCreate(['name' => "{$action}_{$resource}"]);
                    }
                }
            }
        }

        // --- 2. Create Roles ---

        // A. Super Admin (Global Role)
        // Created with company_id = null, so it's a system-wide role.
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'company_id' => null]);
        // Grant all permissions to Super Admin (optional if utilizing Gate::before)
        $superAdmin->givePermissionTo(Permission::all());

        // B. Company-Specific Roles (Accountant, Managers, etc.)
        // These roles appear in the Filament Shield UI for the Tenant.
        $company = \App\Models\Company::first();

        if ($company) {
            // 1. Accountant
            $accountant = Role::firstOrCreate(['name' => 'accountant', 'company_id' => $company->id]);
            $accountant->givePermissionTo([
                'view_any_journal_entry', 'view_journal_entry', 'create_journal_entry', 'update_journal_entry', 'post_journal_entry', 'reverse_journal_entry',
                'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice', 'confirm_invoice',
                'view_any_vendor_bill', 'view_vendor_bill', 'create_vendor_bill', 'update_vendor_bill', 'confirm_vendor_bill',
                'view_financial_reports',
                'view_any_partner', 'view_partner', 'create_partner', 'update_partner',
            ]);

            // 2. Inventory Manager
            $inventoryManager = Role::firstOrCreate(['name' => 'inventory_manager', 'company_id' => $company->id]);
            $inventoryManager->givePermissionTo([
                'view_any_product', 'view_product', 'create_product', 'update_product',
                'view_any_stock_move', 'view_stock_move', 'confirm_stock_move',
                'validate_stock_picking',
                'view_any_warehouse', 'view_warehouse', 'create_warehouse', 'update_warehouse',
            ]);

            // 3. Sales Manager
            $salesManager = Role::firstOrCreate(['name' => 'sales_manager', 'company_id' => $company->id]);
            $salesManager->givePermissionTo([
                'view_any_quote', 'view_quote', 'create_quote', 'update_quote',
                'view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice', 'confirm_invoice', 'cancel_invoice',
                'view_any_partner', 'view_partner', 'create_partner', 'update_partner',
            ]);

            // 4. Employee (Basic)
            $employee = Role::firstOrCreate(['name' => 'employee', 'company_id' => $company->id]);
            $employee->givePermissionTo([
                'view_any_product', 'view_product',
            ]);
        }

        // --- 3. Assign Super Admin to Default User ---
        // We assign the Global Super Admin role (company_id = null) to the user.

        $user = \App\Models\User::where('email', 'admin@jmeryar.com')->first();

        if ($user) {
            // Manually checking db to avoid 'User does not belong to team' errors
            $exists = DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('role_id', $superAdmin->id)
                ->exists();

            if (! $exists) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $superAdmin->id,
                    'model_type' => get_class($user),
                    'model_id' => $user->id,
                    'company_id' => null, // Global
                ]);
            }
        }
    }
}
