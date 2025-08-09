<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\User;
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

        // Fetch Hawre Trading Group partner
        $partner = Partner::where('name', 'Hawre Trading Group')->first();
        if (!$partner) {
            throw new \Exception('Partner "Hawre Trading Group" not found. Please run PartnerSeeder.');
        }

        // Fetch Bank Journal
        $journal = Journal::where('name->en', 'Bank (IQD)')->first();
        if (!$journal) {
            throw new \Exception('Journal "Bank" not found. Please run JournalSeeder.');
        }

        // Fetch the invoice from Step 6 (assuming it's the latest or you can specify)
        $invoice = Invoice::orderBy('id', 'desc')->first();
        if (!$invoice) {
            throw new \Exception('No invoice found. Please run InvoiceSeeder.');
        }

        // Create the payment
        Payment::create([
            'company_id' => $company->id,
            'currency_id' => $company->currency_id,
            'payment_type' => 'inbound', // Cash receipt
            'paid_to_from_partner_id' => $partner->id,
            'amount' => 5000000,
            'payment_date' => Date::now(),
            'journal_id' => $journal->id,
            'status' => Payment::STATUS_DRAFT,
            'reference' => 'Inbound cash receipt from Hawre Trading Group',
        ]);
    }
}
