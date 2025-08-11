<?php

namespace Database\Seeders;

use App\Enums\Partners\PartnerType;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\PaymentDocumentLink;
use App\Models\VendorBill;
use Illuminate\Database\Seeder;

class PaymentDocumentLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payments = Payment::all();

        foreach ($payments as $payment) {
            if ($payment->payment_type === 'customer') {
                $invoice = Invoice::where('partner_id', $payment->partner_id)
                                  ->where('company_id', $payment->company_id)
                                  ->first();

                if ($invoice) {
                    PaymentDocumentLink::updateOrCreate(
                        [
                            'payment_id' => $payment->id,
                            'document_id' => $invoice->id,
                            'document_type' => 'invoice',
                        ],
                        [
                            'amount' => $payment->amount,
                            'notes' => 'Sample payment document link for testing',
                        ]
                    );
                }
            } elseif ($payment->payment_type === PartnerType::Vendor) {
                $vendorBill = VendorBill::where('partner_id', $payment->partner_id)
                                        ->where('company_id', $payment->company_id)
                                        ->first();

                if ($vendorBill) {
                    PaymentDocumentLink::updateOrCreate(
                        [
                            'payment_id' => $payment->id,
                            'document_id' => $vendorBill->id,
                            'document_type' => 'vendor_bill',
                        ],
                        [
                            'amount' => $payment->amount,
                            'notes' => 'Sample payment document link for testing',
                        ]
                    );
                }
            }
        }
    }
}
