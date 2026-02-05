<?php

namespace Kezi\Foundation\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Seeds global permissions (company-agnostic).
 * Roles are company-scoped and created via SetupCompanyRolesAction.
 */
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionsByGroup = [
            'Accounting' => [
                'journal_entry' => ['view_any', 'view', 'create', 'update', 'delete', 'reverse'],
                'post_journal_entry',
            ],
            'Sales' => [
                'invoice' => ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'],
                'quote' => ['view_any', 'view', 'create', 'update', 'delete'],
                'confirm_invoice',
                'cancel_invoice',
            ],
            'Purchase' => [
                'vendor_bill' => ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'],
                'purchase_order' => ['view_any', 'view', 'create', 'update', 'delete'],
                'confirm_vendor_bill',
            ],
            'Inventory' => [
                'product' => ['view_any', 'view', 'create', 'update', 'delete'],
                'stock_move' => ['view_any', 'view'],
                'warehouse' => ['view_any', 'view', 'create', 'update', 'delete'],
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
                    /** @var list<string> $actions */
                    foreach ($actions as $action) {
                        Permission::firstOrCreate(['name' => "{$action}_{$resource}"]);
                    }
                }
            }
        }
    }
}
