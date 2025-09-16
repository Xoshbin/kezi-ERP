<?php

namespace Database\Seeders;

use App\Enums\Products\ProductType;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        // --- Service Products ---
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'TV-001'],
            ['name' => 'تى فى 32', 'type' => ProductType::Storable]
        );
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'REFRIGERATOR-001'],
            ['name' => 'سەلاچە', 'type' => ProductType::Storable]
        );
    }
}
