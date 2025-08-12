<?php

namespace Database\Seeders;

use App\Enums\Products\ProductType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Product;
use Brick\Money\Money;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        $currencyCode = $company->currency->code;

        // --- Fetch all necessary accounts using their unique codes ---
        $consultingRevenueAccount = Account::where('code', '430101')->where('company_id', $company->id)->firstOrFail();
        $serviceRevenueAccount = Account::where('code', '420101')->where('company_id', $company->id)->firstOrFail();
        $productSalesAccount = Account::where('code', '410101')->where('company_id', $company->id)->firstOrFail();
        $cogsAccount = Account::where('code', '510101')->where('company_id', $company->id)->firstOrFail();

        // FIXED: Fetching the required accounts for inventory management
        $inventoryAccount = Account::where('code', '130101')->where('company_id', $company->id)->firstOrFail();
        $stockInputAccount = Account::where('code', '210201')->where('company_id', $company->id)->firstOrFail();
        $priceDifferenceAccount = Account::where('code', '510301')->where('company_id', $company->id)->firstOrFail();

        // --- Service Products ---
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'CONS-001'],
            ['name' => 'Consulting Services', 'type' => ProductType::Service, 'unit_price' => Money::of(150000, $currencyCode), 'income_account_id' => $consultingRevenueAccount->id, 'expense_account_id' => $cogsAccount->id]
        );
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'DEV-001'],
            ['name' => 'Development Services', 'type' => ProductType::Service, 'unit_price' => Money::of(250000, $currencyCode), 'income_account_id' => $serviceRevenueAccount->id, 'expense_account_id' => $cogsAccount->id]
        );

        // --- Storable Products (Now with full inventory account configuration) ---
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'PROD-ROUTER-01'],
            [
                'name' => 'Wireless Router', 'type' => ProductType::Storable, 'unit_price' => Money::of('1200000', $currencyCode),
                'income_account_id' => $productSalesAccount->id, 'expense_account_id' => $cogsAccount->id,
                'default_inventory_account_id' => $inventoryAccount->id,
                'default_cogs_account_id' => $cogsAccount->id,
                'default_stock_input_account_id' => $stockInputAccount->id,
                'default_price_difference_account_id' => $priceDifferenceAccount->id,
            ]
        );
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'PROD-CABLE-01'],
            [
                'name' => 'CAT6 Ethernet Cable (30m)', 'type' => ProductType::Storable, 'unit_price' => Money::of('50000', $currencyCode),
                'income_account_id' => $productSalesAccount->id, 'expense_account_id' => $cogsAccount->id,
                'default_inventory_account_id' => $inventoryAccount->id,
                'default_cogs_account_id' => $cogsAccount->id,
                'default_stock_input_account_id' => $stockInputAccount->id,
                'default_price_difference_account_id' => $priceDifferenceAccount->id,
            ]
        );
        Product::updateOrCreate(
            ['company_id' => $company->id, 'sku' => 'PROD-SWITCH-01'],
            [
                'name' => 'Network Switch', 'type' => ProductType::Storable, 'unit_price' => Money::of('3500000', $currencyCode),
                'income_account_id' => $productSalesAccount->id, 'expense_account_id' => $cogsAccount->id,
                'default_inventory_account_id' => $inventoryAccount->id,
                'default_cogs_account_id' => $cogsAccount->id,
                'default_stock_input_account_id' => $stockInputAccount->id,
                'default_price_difference_account_id' => $priceDifferenceAccount->id,
            ]
        );
    }
}
