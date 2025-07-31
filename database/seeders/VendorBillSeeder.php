<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use App\Models\VendorBill;
use Brick\Money\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class VendorBillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        $vendor = Partner::where('name', 'Paykar Tech Supplies')->firstOrFail();

        // Create the Vendor Bill (header)
        $vendorBill = VendorBill::updateOrCreate(
            [
            'company_id' => $company->id,
            'vendor_id' => $vendor->id,
            'bill_reference' => 'KE-LAPTOP-001',
            ],
            [
            'bill_date' => Carbon::today(),
            'accounting_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(30),
            'status' => VendorBill::STATUS_DRAFT,
            'currency_id' => $company->currency_id,
            'total_amount' => Money::of(3000000, 'IQD'),
            'total_tax' => 0,
            ]
        );

        // Add Vendor Bill Line (debit IT Equipment asset account)
        // Add Vendor Bill Line (debit IT Equipment asset account)
        // Make sure the account ID exists in the accounts table
        $validAccountId = 1500;
        if (!\DB::table('accounts')->where('id', $validAccountId)->exists()) {
            $validAccountId = \DB::table('accounts')->value('id'); // fallback to first available account
        }
        $vendorBill->lines()->updateOrCreate(
            [
            'description' => 'High-End Laptop for Business Use',
            'expense_account_id' => $validAccountId, // IT Equipment asset account
            ],
            [
            'quantity' => 1,
            'unit_price' => Money::of(3000000, 'IQD'),
            ]
        );
    }
}
