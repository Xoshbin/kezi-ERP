<?php

namespace App\Actions\Payments;

use App\DataTransferObjects\Payments\UpdatePaymentDTO;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\VendorBill;
use App\Enums\Payments\PaymentType;
use App\Enums\Payments\PaymentStatus;
use App\Services\Accounting\LockDateService;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdatePaymentAction
{
    public function __construct(private readonly LockDateService $lockDateService)
    {
    }

    public function execute(UpdatePaymentDTO $dto): Payment
    {
        $payment = $dto->payment;

        if ($payment->status !== PaymentStatus::Draft) {
            throw new UpdateNotAllowedException('Only draft payments can be updated.');
        }

        if (empty($dto->document_links)) {
            throw new InvalidArgumentException('A payment must be linked to at least one document.');
        }

        $this->lockDateService->enforce($payment->company, \Carbon\Carbon::parse($dto->payment_date));

        return DB::transaction(function () use ($dto, $payment) {
            $currencyCode = Currency::find($dto->currency_id)->code;
            $totalAmount = Money::of(0, $currencyCode);
            $documentTypes = [];
            $partnerId = null;

            // Determine totals, partner, and payment type from linked documents
            foreach ($dto->document_links as $link) {
                $totalAmount = $totalAmount->plus($link->amount_applied);
                $documentTypes[$link->document_type] = true;

                if (!$partnerId) {
                    if ($link->document_type === 'invoice') {
                        $partnerId = Invoice::findOrFail($link->document_id)->customer_id;
                    } elseif ($link->document_type === 'vendor_bill') {
                        $partnerId = VendorBill::findOrFail($link->document_id)->vendor_id;
                    }
                }
            }

            if (count($documentTypes) > 1) {
                throw new InvalidArgumentException('A payment cannot be linked to both invoices and vendor bills simultaneously.');
            }
            $paymentType = key($documentTypes) === 'invoice' ? PaymentType::Inbound : PaymentType::Outbound;

            // Update the parent Payment record
            $payment->update([
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'payment_date' => $dto->payment_date,
                'reference' => $dto->reference,
                'amount' => $totalAmount,
                'payment_type' => $paymentType,
                'paid_to_from_partner_id' => $partnerId,
            ]);

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

            return $payment->fresh();
        });
    }
}
