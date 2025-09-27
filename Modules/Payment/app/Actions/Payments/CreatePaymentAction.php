<?php

namespace Modules\Payment\Actions\Payments;

use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\LockDateService;
use App\Services\Payments\Strategies\SettlementStrategy;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreatePaymentAction
{
    public function __construct(private readonly \Modules\Accounting\Services\Accounting\LockDateService $lockDateService) {}

    public function execute(CreatePaymentDTO $dto, User $user): \Modules\Payment\Models\Payment
    {
        // Infer flow from presence of document links: if provided => settlement; else => partner advance/credit
        $isSettlement = ! empty($dto->document_links);
        if ($isSettlement === true && empty($dto->document_links)) {
            throw new InvalidArgumentException('Settlement payments must be linked to at least one document.');
        }
        if ($isSettlement === false && empty($dto->paid_to_from_partner_id)) {
            throw new InvalidArgumentException('Payments without document links must specify a partner.');
        }

        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->payment_date));

        return DB::transaction(function () use ($dto) {
            $currencyCode = \Modules\Foundation\Models\Currency::findOrFail($dto->currency_id)->code;

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
                            $partnerId = \Modules\Sales\Models\Invoice::findOrFail($link->document_id)->customer_id;
                        } elseif ($link->document_type === 'vendor_bill') {
                            $partnerId = \Modules\Purchase\Models\VendorBill::findOrFail($link->document_id)->vendor_id;
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

            // Create the parent Payment record
            $payment = \Modules\Payment\Models\Payment::create([
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'payment_date' => $dto->payment_date,

                'payment_method' => $dto->payment_method,
                'reference' => $dto->reference,
                'amount' => $totalAmount,
                'payment_type' => $paymentType,
                'paid_to_from_partner_id' => $partnerId,
                'status' => PaymentStatus::Draft,
            ]);

            // Handle settlement payments (document links)
            if (! empty($dto->document_links)) {
                $settlementStrategy = app(SettlementStrategy::class);
                $settlementStrategy->executeCreate($payment, $dto);
            }
            // For partner advances/credits (non-settlement), no additional logic needed here

            return $payment;
        });
    }
}
