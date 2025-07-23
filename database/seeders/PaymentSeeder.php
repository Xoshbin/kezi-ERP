<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        // Fetch the company
        $company = Company::where('name', 'Jmeryar Solutions')->first();
        if (!$company) {
            throw new \Exception('Company "Jmeryar Solutions" not found. Please run CompanySeeder.');
        }

        // Fetch the admin user
        $adminUser = User::where('name', 'Admin User')->first();
        if (!$adminUser) {
            throw new \Exception('User "Admin User" not found. Please run UserSeeder.');
        }

        // Fetch customer partners
        $customers = Partner::where('type', 'customer')->limit(3)->get();
        if ($customers->count() < 3) {
            throw new \Exception('Not enough customer partners found. Please run PartnerSeeder.');
        }

        // Fetch vendor partners
        $vendors = Partner::where('type', 'vendor')->limit(3)->get();
        if ($vendors->count() < 3) {
            throw new \Exception('Not enough vendor partners found. Please run PartnerSeeder.');
        }

        // Fetch the bank account
        $bankAccount = Account::where('name', 'Bank Account')->first();
        if (!$bankAccount) {
            throw new \Exception('Account "Bank Account" not found. Please run AccountSeeder.');
        }

        // Fetch sample invoices
        $invoices = Invoice::where('status', 'posted')->limit(3)->get();
        if ($invoices->count() < 3) {
            throw new \Exception('Not enough posted invoices found. Please run InvoiceSeeder.');
        }

        // Fetch sample vendor bills
        $vendorBills = VendorBill::where('status', 'posted')->limit(3)->get();
        if ($vendorBills->count() < 3) {
            throw new \Exception('Not enough posted vendor bills found. Please run VendorBillSeeder.');
        }

        // Seed customer payments
        for ($i = 0; $i < 3; $i++) {
            Payment::updateOrCreate(
                ['reference' => 'PAY-2025-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'user_id' => $adminUser->id,
                    'partner_id' => $customers[$i]->id,
                    'account_id' => $bankAccount->id,
                    'payment_date' => Date::now(),
                    'amount' => $invoices[$i]->total,
                    'status' => 'posted',
                    'payment_method' => ($i % 2 == 0) ? 'cash' : 'bank_transfer',
                    'notes' => 'Sample payment for testing',
                    'paymentable_id' => $invoices[$i]->id,
                    'paymentable_type' => Invoice::class,
                ]
            );
        }

        // Seed vendor payments
        for ($i = 0; $i < 3; $i++) {
            Payment::updateOrCreate(
                ['reference' => 'PAY-2025-' . str_pad($i + 4, 3, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'user_id' => $adminUser->id,
                    'partner_id' => $vendors[$i]->id,
                    'account_id' => $bankAccount->id,
                    'payment_date' => Date::now(),
                    'amount' => $vendorBills[$i]->total,
                    'status' => 'posted',
                    'payment_method' => ($i % 2 == 0) ? 'cash' : 'bank_transfer',
                    'notes' => 'Sample payment for testing',
                    'paymentable_id' => $vendorBills[$i]->id,
                    'paymentable_type' => VendorBill::class,
                ]
            );
        }
    }
}
