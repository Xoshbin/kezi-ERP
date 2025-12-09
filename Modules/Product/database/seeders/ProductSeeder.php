<?php

namespace Modules\Product\Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Modules\Product\Models\Product;
use Modules\Accounting\Models\Account;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Models\ReorderingRule;
use Modules\Product\Enums\Products\ProductType;
use Modules\Inventory\Enums\Inventory\ReorderingRoute;
use Modules\Inventory\Enums\Inventory\ValuationMethod;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();

        // --- Commented out old products for later use ---
        /*
        // Resolve a valid expense account (COGS)
        $costOfRevenue = Account::where('company_id', $company->id)
            ->where('code', '510101') // Cost of Goods Sold (COGS)
            ->firstOrFail();

        // --- Storable Products ---
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'TV-001'],
            [
                'name' => 'تى فى 32',
                'type' => ProductType::Storable,
                'income_account_id' => $incomeAccount->id,
                'expense_account_id' => $costOfRevenue->id,
                'default_inventory_account_id' => $costOfRevenue->id,
            ]
        );
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'REFRIGERATOR-001'],
            [
                'name' => 'سەلاچە',
                'type' => ProductType::Storable,
                'income_account_id' => $incomeAccount->id,
                'expense_account_id' => $costOfRevenue->id,
                'default_inventory_account_id' => $costOfRevenue->id,
            ]
        );
        */

        // --- New Products ---

        // Resolve accounts by code
        $inventoryAccount = Account::where('company_id', $company->id)->where('code', '130102')->firstOrFail(); // Inventory Asset (IQD)
        $stockInputAccount = Account::where('company_id', $company->id)->where('code', '210202')->firstOrFail(); // Stock Input Account (IQD)
        $costOfRevenue = Account::where('company_id', $company->id)->where('code', '500100')->firstOrFail(); // Cost of Goods Sold (IQD)
        $incomeAccount = Account::where('company_id', $company->id)->where('code', '410102')->firstOrFail(); // Cost of Goods Sold (IQD)

        // Product A: High-End Graphics Cards (FIFO Valuation)
        $productA = Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'GPU-RTX4090'],
            [
                'name' => 'NVIDIA RTX 4090 Graphics Card',
                'type' => \Modules\Product\Enums\Products\ProductType::Storable,
                'inventory_valuation_method' => ValuationMethod::FIFO,
                'unit_price' => 2500000,
                'default_inventory_account_id' => $inventoryAccount->id,
                'default_stock_input_account_id' => $stockInputAccount->id,
                'income_account_id' => $incomeAccount->id,
                'expense_account_id' => $costOfRevenue->id,
                'lot_tracking_enabled' => true, // Serial number tracking
            ]
        );

        // Product B: Memory Modules (AVCO Valuation)
        $productB = Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'RAM-DDR5-32GB'],
            [
                'name' => 'DDR5 32GB Memory Module',
                'type' => \Modules\Product\Enums\Products\ProductType::Storable,
                'inventory_valuation_method' => ValuationMethod::AVCO,
                'unit_price' => 400000,
                'default_inventory_account_id' => $inventoryAccount->id,
                'default_stock_input_account_id' => $stockInputAccount->id,
                'income_account_id' => $incomeAccount->id,
                'expense_account_id' => $costOfRevenue->id,
                'lot_tracking_enabled' => true, // Batch tracking
            ]
        );

        // Product C: Storage Drives (LIFO Valuation)
        $productC = Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'SSD-2TB-NVME'],
            [
                'name' => '2TB NVMe SSD Drive',
                'type' => \Modules\Product\Enums\Products\ProductType::Storable,
                'inventory_valuation_method' => ValuationMethod::LIFO,
                'unit_price' => 300000,
                'default_inventory_account_id' => $inventoryAccount->id,
                'default_stock_input_account_id' => $stockInputAccount->id,
                'income_account_id' => $incomeAccount->id,
                'expense_account_id' => $costOfRevenue->id,
                'lot_tracking_enabled' => true, // Batch tracking with expiration dates
            ]
        );

        // Create reordering rules as per end-to-end scenario documentation
        $warehouseLocation = StockLocation::where('company_id', $company->id)
            ->where('name', 'Warehouse')
            ->first();

        if ($warehouseLocation) {
            // GPU-RTX4090 Reorder Rule: Min: 5, Max: 20, Safety Stock: 2
            ReorderingRule::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'product_id' => $productA->id,
                    'location_id' => $warehouseLocation->id,
                ],
                [
                    'min_qty' => 5,
                    'max_qty' => 20,
                    'safety_stock' => 2,
                    'multiple' => 1,
                    'route' => ReorderingRoute::MinMax,
                    'lead_time_days' => 7,
                    'active' => true,
                ]
            );

            // RAM-DDR5-32GB Reorder Rule: Min: 20, Max: 100, Safety Stock: 10
            ReorderingRule::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'product_id' => $productB->id,
                    'location_id' => $warehouseLocation->id,
                ],
                [
                    'min_qty' => 20,
                    'max_qty' => 100,
                    'safety_stock' => 10,
                    'multiple' => 5,
                    'route' => ReorderingRoute::MinMax,
                    'lead_time_days' => 5,
                    'active' => true,
                ]
            );

            // SSD-2TB-NVME Reorder Rule: Min: 15, Max: 50, Safety Stock: 5
            ReorderingRule::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'product_id' => $productC->id,
                    'location_id' => $warehouseLocation->id,
                ],
                [
                    'min_qty' => 15,
                    'max_qty' => 50,
                    'safety_stock' => 5,
                    'multiple' => 5,
                    'route' => ReorderingRoute::MinMax,
                    'lead_time_days' => 10,
                    'active' => true,
                ]
            );
        }
    }
}
