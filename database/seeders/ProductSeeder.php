<?php

namespace Database\Seeders;

use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();

        // --- Commented out old products for later use ---
        /*
        // Resolve a valid expense account (COGS)
        $cogsAccount = Account::where('company_id', $company->id)
            ->where('code', '510101') // Cost of Goods Sold (COGS)
            ->firstOrFail();

        // --- Storable Products ---
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'TV-001'],
            [
                'name' => 'تى فى 32',
                'type' => ProductType::Storable,
                'expense_account_id' => $cogsAccount->id,
                'default_inventory_account_id' => $cogsAccount->id,
            ]
        );
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'REFRIGERATOR-001'],
            [
                'name' => 'سەلاچە',
                'type' => ProductType::Storable,
                'expense_account_id' => $cogsAccount->id,
                'default_inventory_account_id' => $cogsAccount->id,
            ]
        );
        */

        // --- New Products ---

        // Resolve accounts by code
        $inventoryAccount = Account::where('company_id', $company->id)->where('code', '130102')->firstOrFail(); // Inventory Asset (IQD)
        $stockInputAccount = Account::where('company_id', $company->id)->where('code', '210202')->firstOrFail(); // Stock Input Account (IQD)
        $cogsAccount = Account::where('company_id', $company->id)->where('code', '510102')->firstOrFail(); // Cost of Goods Sold (IQD)

        // Product A: High-End Graphics Cards (FIFO Valuation)
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'GPU-RTX4090'],
            [
                'name' => 'NVIDIA RTX 4090 Graphics Card',
                'type' => ProductType::Storable,
                'inventory_valuation_method' => ValuationMethod::FIFO,
                'unit_price' => 2500000,
                'default_inventory_account_id' => $inventoryAccount->id,
                'default_stock_input_account_id' => $stockInputAccount->id,
                'expense_account_id' => $cogsAccount->id,
                'lot_tracking_enabled' => true, // Serial number tracking
            ]
        );

        // Product B: Memory Modules (AVCO Valuation)
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'RAM-DDR5-32GB'],
            [
                'name' => 'DDR5 32GB Memory Module',
                'type' => ProductType::Storable,
                'inventory_valuation_method' => ValuationMethod::AVCO,
                'unit_price' => 400000,
                'default_inventory_account_id' => $inventoryAccount->id,
                'default_stock_input_account_id' => $stockInputAccount->id,
                'expense_account_id' => $cogsAccount->id,
                'lot_tracking_enabled' => true, // Batch tracking
            ]
        );

        // Product C: Storage Drives (LIFO Valuation)
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'SSD-2TB-NVME'],
            [
                'name' => '2TB NVMe SSD Drive',
                'type' => ProductType::Storable,
                'inventory_valuation_method' => ValuationMethod::LIFO,
                'unit_price' => 300000,
                'default_inventory_account_id' => $inventoryAccount->id,
                'default_stock_input_account_id' => $stockInputAccount->id,
                'expense_account_id' => $cogsAccount->id,
                'lot_tracking_enabled' => true, // Batch tracking with expiration dates
            ]
        );
    }
}
