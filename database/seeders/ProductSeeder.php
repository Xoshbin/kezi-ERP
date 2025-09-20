<?php

namespace Database\Seeders;

use App\Enums\Products\ProductType;
use App\Enums\Products\ProductValuation;
use App\Enums\Products\TrackingType;
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
        $inventoryAccount = Account::where('company_id', $company->id)->where('code', '1200')->firstOrFail();
        $stockInputAccount = Account::where('company_id', $company->id)->where('code', '1210')->firstOrFail();
        $cogsAccount = Account::where('company_id', $company->id)->where('code', '5000')->firstOrFail();

        // Product A: High-End Graphics Cards (FIFO Valuation)
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'GPU-RTX4090'],
            [
                'name' => 'NVIDIA RTX 4090 Graphics Card',
                'type' => ProductType::Storable,
                'valuation' => ProductValuation::Fifo,
                'unit_price' => 2500000,
                'default_inventory_account_id' => $inventoryAccount->id,
                'stock_input_account_id' => $stockInputAccount->id,
                'expense_account_id' => $cogsAccount->id,
                'tracking' => TrackingType::SerialNumber,
                'min_stock' => 5,
                'max_stock' => 20,
                'safety_stock' => 2,
            ]
        );

        // Product B: Memory Modules (AVCO Valuation)
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'RAM-DDR5-32GB'],
            [
                'name' => 'DDR5 32GB Memory Module',
                'type' => ProductType::Storable,
                'valuation' => ProductValuation::Avco,
                'unit_price' => 400000,
                'default_inventory_account_id' => $inventoryAccount->id,
                'stock_input_account_id' => $stockInputAccount->id,
                'expense_account_id' => $cogsAccount->id,
                'tracking' => TrackingType::Batch,
                'min_stock' => 20,
                'max_stock' => 100,
                'safety_stock' => 10,
            ]
        );

        // Product C: Storage Drives (LIFO Valuation)
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'SSD-2TB-NVME'],
            [
                'name' => '2TB NVMe SSD Drive',
                'type' => ProductType::Storable,
                'valuation' => ProductValuation::Lifo,
                'unit_price' => 300000,
                'default_inventory_account_id' => $inventoryAccount->id,
                'stock_input_account_id' => $stockInputAccount->id,
                'expense_account_id' => $cogsAccount->id,
                'tracking' => TrackingType::Batch, // Assuming expiration dates are part of batch tracking
                'min_stock' => 15,
                'max_stock' => 50,
                'safety_stock' => 5,
            ]
        );
    }
}
