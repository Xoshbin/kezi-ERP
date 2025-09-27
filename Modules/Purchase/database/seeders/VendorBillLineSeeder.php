<?php

namespace Modules\Purchase\Database\Seeders;

use App\Models\Account;
use App\Models\Product;
use App\Models\Tax;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use Illuminate\Database\Seeder;

class VendorBillLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $vendorBills = VendorBill::where('status', 'draft')->get();
        // $products = Product::limit(3)->get();
        // $tax = Tax::where('name->en', 'VAT 18%')->firstOrFail();

        // if ($products->count() < 3) {
        //     throw new \Exception('Not enough products found to seed vendor bill lines.');
        // }

        // foreach ($vendorBills as $bill) {
        //     VendorBillLine::updateOrCreate(
        //         ['vendor_bill_id' => $bill->id, 'product_id' => $products[0]->id],
        //         [
        //             'tax_id' => $tax->id,
        //             'quantity' => 2,
        //             'unit_price' => $products[0]->price,
        //             'description' => $products[0]->name,
        //             'discount' => 0,
        //             'amount' => 2 * $products[0]->price,
        //         ]
        //     );

        //     VendorBillLine::updateOrCreate(
        //         ['vendor_bill_id' => $bill->id, 'product_id' => $products[1]->id],
        //         [
        //             'tax_id' => $tax->id,
        //             'quantity' => 3,
        //             'unit_price' => $products[1]->price,
        //             'description' => $products[1]->name,
        //             'discount' => 0,
        //             'amount' => 3 * $products[1]->price,
        //         ]
        //     );
        // }

        $laptopBill = \Modules\Purchase\Models\VendorBill::where('bill_reference', 'KE-LAPTOP-001')->first();
        if ($laptopBill) {
            $itEquipmentAccount = \Modules\Accounting\Models\Account::where('code', '150301')->firstOrFail();
            VendorBillLine::updateOrCreate(
                ['vendor_bill_id' => $laptopBill->id, 'description' => 'High-End Laptop for Business Use'],
                [
                    'expense_account_id' => $itEquipmentAccount->id,
                    'quantity' => 1,
                    'unit_price' => 3000000,
                ]
            );
        }
    }
}
