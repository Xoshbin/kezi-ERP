<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use App\Models\VendorBill;
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
        $user = User::firstOrFail();
        $vendors = Partner::where('type', 'vendor')->limit(3)->get();

        // if ($vendors->count() < 3) {
        //     throw new \Exception('Not enough vendor partners found to seed vendor bills.');
        // }

        // VendorBill::updateOrCreate(
        //     ['company_id' => $company->id, 'partner_id' => $vendors[0]->id, 'reference' => 'BILL-2025-001'],
        //     [
        //         'user_id' => $user->id,
        //         'bill_date' => Carbon::now(),
        //         'due_date' => Carbon::now()->addDays(30),
        //         'status' => 'draft',
        //         'notes' => 'Sample vendor bill for testing',
        //     ]
        // );

        // VendorBill::updateOrCreate(
        //     ['company_id' => $company->id, 'partner_id' => $vendors[1]->id, 'reference' => 'BILL-2025-002'],
        //     [
        //         'user_id' => $user->id,
        //         'bill_date' => Carbon::now(),
        //         'due_date' => Carbon::now()->addDays(30),
        //         'status' => 'draft',
        //         'notes' => 'Sample vendor bill for testing',
        //     ]
        // );

        // VendorBill::updateOrCreate(
        //     ['company_id' => $company->id, 'partner_id' => $vendors[2]->id, 'reference' => 'BILL-2025-003'],
        //     [
        //         'user_id' => $user->id,
        //         'bill_date' => Carbon::now(),
        //         'due_date' => Carbon::now()->addDays(30),
        //         'status' => 'draft',
        //         'notes' => 'Sample vendor bill for testing',
        //     ]
        // );

        $karadaVendor = Partner::firstOrFail();
        VendorBill::updateOrCreate(
            ['company_id' => $company->id, 'vendor_id' => $karadaVendor->id, 'bill_reference' => 'KE-LAPTOP-001'],
            [
                'bill_date' => Carbon::now(),
                'accounting_date' => Carbon::now(),
                'due_date' => Carbon::now()->addDays(30),
                'status' => VendorBill::TYPE_DRAFT,
                'currency_id' => $company->currency_id,
                'total_amount' => 3000000,
                'total_tax' => 0,
            ]
        );
    }
}
