<?php

namespace App\Actions\Payments;

use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\Accounting\LockDateService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreatePaymentAction
{
    public function __construct(private readonly LockDateService $lockDateService)
    {
    }

    public function execute(CreatePaymentDTO $dto, User $user): Payment
    {
        if (empty($dto->document_links)) {
            throw new InvalidArgumentException('A payment must be linked to at least one document.');
        }
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->payment_date));

        return DB::transaction(function () use ($dto, $user) {
            $currencyCode = Currency::find($dto->currency_id)->code;
            $totalAmount = Money::of(0, $currencyCode);
            $documentTypes = [];
            $partnerId = null;

            // Determine totals, partner, and payment type from linked documents
            foreach ($dto->document_links as $link) {
                $totalAmount = $totalAmount->plus(Money::of($link->amount_applied, $currencyCode));
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
            $paymentType = key($documentTypes) === 'invoice' ? Payment::TYPE_INBOUND : Payment::TYPE_OUTBOUND;

            // Create the parent Payment record
            $payment = Payment::create([
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'payment_date' => $dto->payment_date,
                'reference' => $dto->reference,
                'amount' => $totalAmount,
                'payment_type' => $paymentType,
                'paid_to_from_partner_id' => $partnerId,
                'status' => Payment::STATUS_DRAFT,
            ]);

            // Create the links
            foreach ($dto->document_links as $link) {
                $linkData = [
                    'amount_applied' => Money::of($link->amount_applied, $currencyCode),
                ];
                if ($link->document_type === 'invoice') {
                    $linkData['invoice_id'] = $link->document_id;
                } else { // vendor_bill
                    $linkData['vendor_bill_id'] = $link->document_id;
                }
                $payment->paymentDocumentLinks()->create($linkData);
            }

            return $payment;
        });
    }
}
