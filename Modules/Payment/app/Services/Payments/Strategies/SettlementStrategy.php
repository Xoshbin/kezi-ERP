<?php

namespace App\Services\Payments\Strategies;

use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Payments\UpdatePaymentDTO;
use App\Models\Payment;

class SettlementStrategy implements PaymentStrategy
{
    /**
     * Execute the strategy for creating a settlement payment.
     * This handles linking payments to invoices and vendor bills.
     */
    public function executeCreate(Payment $payment, CreatePaymentDTO $dto): void
    {
        // Create the payment document links
        foreach ($dto->document_links as $link) {
            $linkData = [
                'company_id' => $dto->company_id,
                'amount_applied' => $link->amount_applied,
            ];

            if ($link->document_type === 'invoice') {
                $linkData['invoice_id'] = $link->document_id;
            } else { // vendor_bill
                $linkData['vendor_bill_id'] = $link->document_id;
            }

            $payment->paymentDocumentLinks()->create($linkData);
        }
    }

    /**
     * Execute the strategy for updating a settlement payment.
     * This handles updating payment document links.
     */
    public function executeUpdate(Payment $payment, UpdatePaymentDTO $dto): void
    {
        // Delete existing links and create new ones
        $payment->paymentDocumentLinks()->delete();

        foreach ($dto->document_links as $link) {
            $linkData = [
                'amount_applied' => $link->amount_applied,
            ];

            if ($link->document_type === 'invoice') {
                $linkData['invoice_id'] = $link->document_id;
            } else { // vendor_bill
                $linkData['vendor_bill_id'] = $link->document_id;
            }

            $payment->paymentDocumentLinks()->create($linkData);
        }
    }
}
