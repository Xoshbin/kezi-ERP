<?php

namespace Database\Seeders;

use App\Actions\Purchases\CreateVendorBillLineAction;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Enums\Partners\PartnerType;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Product;
use App\Models\VendorBill;
use Brick\Money\Money;
use Illuminate\Database\Seeder;

class VendorBillSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();

        $currencyCode = $company->currency->code;

        // --- Fetch Products ---
        $routerProduct = Product::where('sku', 'PROD-ROUTER-01')->firstOrFail();
        $cableProduct = Product::where('sku', 'PROD-CABLE-01')->firstOrFail();
        $switchProduct = Product::where('sku', 'PROD-SWITCH-01')->firstOrFail();

        // --- Fetch Vendors ---
        $vendor1 = Partner::firstOrCreate(['name' => 'Paykar Tech Supplies', 'company_id' => $company->id], ['type' => PartnerType::Vendor]);
        $vendor2 = Partner::firstOrCreate(['name' => 'Hiwa Computer Center', 'company_id' => $company->id], ['type' => PartnerType::Vendor]);

        $createLineAction = resolve(CreateVendorBillLineAction::class);

        // === BILL 1: From Paykar Tech (Total: 10,800,000) ===
        $bill1 = VendorBill::updateOrCreate(
            ['company_id' => $company->id, 'vendor_id' => $vendor1->id, 'bill_reference' => 'PK-INV-2025-001'],
            [
                'bill_date' => now()->subDays(10),
                'accounting_date' => now()->subDays(10),
                'due_date' => now()->addDays(20),
                'status' => 'draft',
                'currency_id' => $company->currency_id,
                'total_amount' => Money::of(0, $currencyCode), // Will be updated by observer
                'total_tax' => Money::of(0, $currencyCode),
            ]
        );
        if ($bill1->wasRecentlyCreated) {
            $createLineAction->execute($bill1, new CreateVendorBillLineDTO(product_id: $routerProduct->id, description: $routerProduct->name, quantity: 10, unit_price: '1000000', expense_account_id: $routerProduct->expense_account_id, tax_id: null, analytic_account_id: null));
            $createLineAction->execute($bill1, new CreateVendorBillLineDTO(product_id: $cableProduct->id, description: $cableProduct->name, quantity: 20, unit_price: '40000', expense_account_id: $cableProduct->expense_account_id, tax_id: null, analytic_account_id: null));
        }

        // === BILL 2: From Hiwa Computer (Total: 16,000,000) ===
        $bill2 = VendorBill::updateOrCreate(
            ['company_id' => $company->id, 'vendor_id' => $vendor2->id, 'bill_reference' => 'HC-INV-2025-002'],
            [
                'bill_date' => now()->subDays(5),
                'accounting_date' => now()->subDays(5),
                'due_date' => now()->addDays(25),
                'status' => 'draft',
                'currency_id' => $company->currency_id,
                'total_amount' => Money::of(0, $currencyCode), // Will be updated by observer
                'total_tax' => Money::of(0, $currencyCode),
            ]
        );
        if ($bill2->wasRecentlyCreated) {
            $createLineAction->execute($bill2, new CreateVendorBillLineDTO(product_id: $switchProduct->id, description: $switchProduct->name, quantity: 5, unit_price: '3200000', expense_account_id: $switchProduct->expense_account_id, tax_id: null, analytic_account_id: null));
        }
    }
}
