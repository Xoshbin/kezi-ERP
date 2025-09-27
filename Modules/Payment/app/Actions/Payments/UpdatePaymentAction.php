<?php

namespace Modules\Payment\Actions\Payments;

use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Foundation\Models\Currency;
use Modules\Payment\Models\Payment;
use Modules\Purchase\Models\VendorBill;
use Modules\Sales\Models\Invoice;

class UpdatePaymentAction
{
    public function __construct(private readonly \Modules\Accounting\Services\Accounting\LockDateService $lockDateService) {}

    public function execute(UpdatePaymentDTO $dto): Payment
    {
        $payment = $dto->payment;

        if ($payment->status !== PaymentStatus::Draft) {
            throw new \Modules\Foundation\Exceptions\UpdateNotAllowedException('Only draft payments can be updated.');
        }

        // Infer flow from presence of document links
        $isSettlement = ! empty($dto->document_links);
        if ($isSettlement && empty($dto->document_links)) {
            throw new InvalidArgumentException('Settlement payments must be linked to at least one document.');
        }
        if (! $isSettlement && empty($dto->paid_to_from_partner_id)) {
            throw new InvalidArgumentException('Payments without document links must specify a partner.');
        }

        $this->lockDateService->enforce($payment->company, Carbon::parse($dto->payment_date));

        return DB::transaction(function () use ($dto, $payment) {
            $currencyCode = Currency::findOrFail($dto->currency_id)->code;

            // Determine payment details based on presence of document links
            if (! empty($dto->document_links)) {
                // For settlement payments, calculate from document links
                $totalAmount = Money::of(0, $currencyCode);
                $documentTypes = [];
                $partnerId = null;

                foreach ($dto->document_links as $link) {
                    $totalAmount = $totalAmount->plus($link->amount_applied);
                    $documentTypes[$link->document_type] = true;

                    if (! $partnerId) {
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
            } else {
                // For direct payments, use provided values
                $totalAmount = $dto->amount;
                $partnerId = $dto->paid_to_from_partner_id;
                $paymentType = $dto->payment_type;
            }

            // Update the parent Payment record
            $payment->update([
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'payment_date' => $dto->payment_date,

                'payment_method' => $dto->payment_method,
                'reference' => $dto->reference,
                'amount' => $totalAmount,
                'payment_type' => $paymentType,
                'paid_to_from_partner_id' => $partnerId,
            ]);

            // Handle settlement payments (document links)
            if (! empty($dto->document_links)) {
                $settlementStrategy = app(SettlementStrategy::class);
                $settlementStrategy->executeUpdate($payment, $dto);
            }
            // For partner advances/credits (non-settlement), no additional logic needed here

            $freshPayment = $payment->fresh();
            if (! $freshPayment) {
                throw new Exception('Failed to refresh payment after update');
            }

            return $freshPayment;
        });
    }
}
