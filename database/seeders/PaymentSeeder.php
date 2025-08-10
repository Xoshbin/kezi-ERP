<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\PaymentDocumentLink;
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

        // Fetch Bank (IQD) Journal
        $journal = Journal::where('name->en', 'Bank (IQD)')->where('company_id', $company->id)->first();
        if (!$journal) {
            throw new \Exception('Journal "Bank (IQD)" not found. Please run JournalSeeder.');
        }

        // Step 7: Receiving Payment from "Hawre Trading Group"
        $hawrePartner = Partner::where('name', 'Hawre Trading Group')->first();
        if (!$hawrePartner) {
            throw new \Exception('Partner "Hawre Trading Group" not found. Please run PartnerSeeder.');
        }

        $invoice = Invoice::where('customer_id', $hawrePartner->id)->orderBy('id', 'desc')->first();
        if (!$invoice) {
            throw new \Exception('Invoice for "Hawre Trading Group" not found. Please run InvoiceSeeder.');
        }

        $inboundPayment = Payment::create([
            'company_id' => $company->id,
            'currency_id' => $company->currency_id,
            'payment_type' => 'inbound',
            'paid_to_from_partner_id' => $hawrePartner->id,
            'amount' => 5000000,
            'payment_date' => Date::now(),
            'journal_id' => $journal->id,
            'status' => 'draft',
            'reference' => 'Payment for invoice from Hawre Trading Group',
        ]);

        PaymentDocumentLink::create([
            'payment_id' => $inboundPayment->id,
            'invoice_id' => $invoice->id,
            'amount_applied' => 5000000,
        ]);


        // Step 8: Paying "Paykar Tech Supplies"
        $paykarPartner = Partner::where('name', 'Paykar Tech Supplies')->first();
        if (!$paykarPartner) {
            throw new \Exception('Partner "Paykar Tech Supplies" not found. Please run PartnerSeeder.');
        }

        $vendorBill = VendorBill::where('vendor_id', $paykarPartner->id)->orderBy('id', 'desc')->first();
        if (!$vendorBill) {
            throw new \Exception('Vendor Bill for "Paykar Tech Supplies" not found. Please run VendorBillSeeder.');
        }

        $outboundPayment = Payment::create([
            'company_id' => $company->id,
            'currency_id' => $company->currency_id,
            'payment_type' => 'outbound',
            'paid_to_from_partner_id' => $paykarPartner->id,
            'amount' => 3000000,
            'payment_date' => Date::now(),
            'journal_id' => $journal->id,
            'status' => 'draft',
            'reference' => 'Payment to Paykar Tech Supplies for laptop',
        ]);

        PaymentDocumentLink::create([
            'payment_id' => $outboundPayment->id,
            'vendor_bill_id' => $vendorBill->id,
            'amount_applied' => 3000000,
        ]);
    }
}
