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

        $currencyCode = $company->currency->code; // Expecting IQD

        // --- Fetch Products created by ProductSeeder ---
        $tvProduct = Product::where('company_id', $company->id)->where('sku', 'TV-001')->firstOrFail();
        $refrigeratorProduct = Product::where('company_id', $company->id)->where('sku', 'REFRIGERATOR-001')->firstOrFail();

        // --- Fetch/Create Vendor ---
        $vendor = Partner::firstOrCreate(
            ['name' => 'Home Appliance Distributor', 'company_id' => $company->id],
            ['type' => PartnerType::Vendor]
        );

        $createLineAction = resolve(CreateVendorBillLineAction::class);

        // === Single BILL: Two line items (TV and Refrigerator) ===
        $bill = VendorBill::updateOrCreate(
            ['company_id' => $company->id, 'vendor_id' => $vendor->id, 'bill_reference' => 'APPL-INV-2025-001'],
            [
                'bill_date' => now()->subDays(2),
                'accounting_date' => now()->subDays(2),
                'due_date' => now()->addDays(28),
                'status' => 'draft',
                'currency_id' => $company->currency_id,
                'total_amount' => Money::of(0, $currencyCode), // Observer will update after lines
                'total_tax' => Money::of(0, $currencyCode),
            ]
        );

        if ($bill->wasRecentlyCreated) {
            // 1) TV: 10 units @ 500,000 IQD
            $createLineAction->execute(
                $bill,
                new CreateVendorBillLineDTO(
                    product_id: $tvProduct->id,
                    description: $tvProduct->name,
                    quantity: 10,
                    unit_price: Money::of('500000', $currencyCode),
                    expense_account_id: $tvProduct->expense_account_id,
                    tax_id: null,
                    analytic_account_id: null
                )
            );

            // 2) Refrigerator: 5 units @ 700,000 IQD
            $createLineAction->execute(
                $bill,
                new CreateVendorBillLineDTO(
                    product_id: $refrigeratorProduct->id,
                    description: $refrigeratorProduct->name,
                    quantity: 5,
                    unit_price: Money::of('700000', $currencyCode),
                    expense_account_id: $refrigeratorProduct->expense_account_id,
                    tax_id: null,
                    analytic_account_id: null
                )
            );
        }
    }
}
