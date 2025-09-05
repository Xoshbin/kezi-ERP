<?php

namespace App\Actions\Payments;

use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\Accounting\LockDateService;
use App\Services\Payments\Strategies\SettlementStrategy;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreatePaymentAction
{
    public function __construct(private readonly LockDateService $lockDateService) {}

    public function execute(CreatePaymentDTO $dto, User $user): Payment
    {
        // Validate based on payment purpose
        if ($dto->payment_purpose === PaymentPurpose::Settlement && empty($dto->document_links)) {
            throw new InvalidArgumentException('Settlement payments must be linked to at least one document.');
        }

        if ($dto->payment_purpose !== PaymentPurpose::Settlement && ! $dto->counterpart_account_id) {
            throw new InvalidArgumentException('Non-settlement payments must have a counterpart account.');
        }

        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->payment_date));

        return DB::transaction(function () use ($dto) {
            $currencyCode = Currency::findOrFail($dto->currency_id)->code;

            // Determine payment details based on purpose
            if ($dto->payment_purpose === PaymentPurpose::Settlement) {
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
                $partnerId = $dto->partner_id;
                $paymentType = $dto->payment_type;
            }

            // Create the parent Payment record
            $payment = Payment::create([
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'payment_date' => $dto->payment_date,
                'payment_purpose' => $dto->payment_purpose,
                'counterpart_account_id' => $dto->counterpart_account_id,
                'reference' => $dto->reference,
                'amount' => $totalAmount,
                'payment_type' => $paymentType,
                'paid_to_from_partner_id' => $partnerId,
                'status' => PaymentStatus::Draft,
            ]);

            // Handle settlement payments (document links)
            if ($dto->payment_purpose === PaymentPurpose::Settlement) {
                $settlementStrategy = app(SettlementStrategy::class);
                $settlementStrategy->executeCreate($payment, $dto);
            }
            // For other payment types (loan, capital injection, etc.), no additional logic needed
            // The counterpart_account_id is already set on the payment record

            return $payment;
        });
    }
}
