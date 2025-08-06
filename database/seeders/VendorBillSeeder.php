<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Brick\Money\Money;
use App\Models\Account;
use App\Models\Company;
use App\Models\Partner;
use App\Models\VendorBill;
use Illuminate\Database\Seeder;
use App\Actions\Purchases\CreateVendorBillLineAction;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;

class VendorBillSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            return;
        }

        $vendor = Partner::where('name', 'Paykar Tech Supplies')->where('company_id', $company->id)->first();
        $itEquipmentAccount = Account::where('name', 'IT Equipment')->where('company_id', $company->id)->first();
        $currencyCode = $company->currency->code;

        // 1. Create the parent Vendor Bill first
        $vendorBill = VendorBill::updateOrCreate(
            [
                'company_id' => $company->id,
                'vendor_id' => $vendor->id,
                'bill_reference' => 'KE-LAPTOP-001',
            ],
            [
                'bill_date' => now(),
                'accounting_date' => Carbon::today(),
                'due_date' => now()->addDays(30),
                'status' => 'draft',
                'currency_id' => $company->currency_id,
                'total_amount' => Money::of(0, $currencyCode), // Will be updated by observer
                'total_tax' => Money::of(0, $currencyCode),
            ]
        );

        // 2. Prepare the DTO for the line item
        $lineDto = new CreateVendorBillLineDTO(
            description: 'High-End Laptop for Business Use',
            quantity: 1,
            unit_price: '3000000', // DTOs can accept clean string representations
            expense_account_id: $itEquipmentAccount->id,
            product_id: null,
            tax_id: null,
            analytic_account_id: null,
        );

        // 3. Resolve the Action from the container and execute it
        $createLineAction = resolve(CreateVendorBillLineAction::class);
        $createLineAction->execute($vendorBill, $lineDto);
    }
}
