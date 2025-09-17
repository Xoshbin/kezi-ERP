<?php

namespace Database\Seeders;

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

        // Resolve a valid expense account (COGS)
        $cogsAccount = Account::where('company_id', $company->id)
            ->where('code', '510101') // Cost of Goods Sold (COGS)
            ->firstOrFail();

        // --- Storable Products ---
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'TV-001'],
            ['name' => 'تى فى 32', 'type' => ProductType::Storable, 'expense_account_id' => $cogsAccount->id]
        );
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'REFRIGERATOR-001'],
            ['name' => 'سەلاچە', 'type' => ProductType::Storable, 'expense_account_id' => $cogsAccount->id]
        );
    }
}
