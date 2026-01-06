<?php

namespace Modules\Payment\Actions\Payments;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Services\CurrencyConverterService;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\Payments\Strategies\SettlementStrategy;
use Modules\Purchase\Models\VendorBill;
use Modules\Sales\Models\Invoice;

class CreatePaymentAction
{
    public function __construct(
        private readonly \Modules\Accounting\Services\Accounting\LockDateService $lockDateService,
        private readonly CurrencyConverterService $currencyConverter
    ) {}

    public function execute(CreatePaymentDTO $dto, User $user): Payment
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

        return DB::transaction(function () use ($dto, $company) {
            $currency = Currency::findOrFail($dto->currency_id);
            $currencyCode = $currency->code;

            // Fetch Exchange Rate for WHT Conversion
            // If payment currency is same as company, rate is 1.0
            $exchangeRate = 1.0;
            if ($currency->id !== $company->currency_id) {
                // Try to fetch rate, fallback to 1.0 if not found (Draft state)
                $exchangeRate = $this->currencyConverter->getExchangeRate(
                    $currency,
                    Carbon::parse($dto->payment_date),
                    $company
                ) ?? 1.0;
            }

            // Adjust calculation for Withholding Tax
            $totalWithheldAmount = Money::of(0, $currencyCode);
            $whtEntriesToCreate = [];

            if (! empty($dto->document_links)) {
                $documentTypes = [];
                $partnerId = null;
                $totalAmount = Money::of(0, $currencyCode); // Reset to recalculate with WHT awareness

                foreach ($dto->document_links as $link) {
                    $linkAmount = $link->amount_applied;
                    $totalAmount = $totalAmount->plus($linkAmount); // Gross Amount clearing debt
                    $documentTypes[$link->document_type] = true;

                    // Determine Partner
                    $documentPartner = null;
                    if ($link->document_type === 'invoice') {
                        $document = Invoice::findOrFail($link->document_id);
                        $documentPartner = $document->customer;
                    } elseif ($link->document_type === 'vendor_bill') {
                        $document = VendorBill::findOrFail($link->document_id);
                        $documentPartner = $document->vendor;
                    }

                    if ($documentPartner && ! $partnerId) {
                        $partnerId = $documentPartner->id;
                    }

                    // WHT Logic: Currently only for Vendor Bills (Outbound)
                    // If Paying a Vendor Bill, and Vendor has WHT Type, we withhold.
                    if ($link->document_type === 'vendor_bill' && $documentPartner && $documentPartner->withholding_tax_type_id) {
                        $whtType = $documentPartner->withholdingTaxType;
                        // Logic: Tax is on the Amount Applied (Gross).
                        // We assume Amount Applied is the Gross amount being cleared.
                        $taxAmount = $whtType->calculateWithholding($linkAmount);

                        if ($taxAmount->isPositive()) {
                            $totalWithheldAmount = $totalWithheldAmount->plus($taxAmount);

                            // Convert amounts to Base Currency for Storage
                            // Note: calculateWithholding returns Money in same currency as input (Payment Currency)
                            // We must convert to Company Base Currency
                            $taxAmountBase = $taxAmount->multipliedBy($exchangeRate, \Brick\Math\RoundingMode::HALF_UP);
                            $linkAmountBase = $linkAmount->multipliedBy($exchangeRate, \Brick\Math\RoundingMode::HALF_UP);

                            $whtEntriesToCreate[] = [
                                'company_id' => $dto->company_id,
                                'withholding_tax_type_id' => $whtType->id,
                                'vendor_id' => $documentPartner->id,
                                'base_amount' => $linkAmountBase, // Base Currency
                                'withheld_amount' => $taxAmountBase, // Base Currency
                                'rate_applied' => $whtType->rate_fraction, // Use fraction 0.05
                                'currency_id' => $dto->currency_id, // Store Original Payment Currency ID
                            ];
                        }
                    }
                }

                if (count($documentTypes) > 1) {
                    throw new InvalidArgumentException('A payment cannot be linked to both invoices and vendor bills simultaneously.');
                }
                $paymentType = key($documentTypes) === 'invoice' ? PaymentType::Inbound : PaymentType::Outbound;

                // ADJUSTMENT: The actual Money Movement (Payment Amount) is Gross - Tax
                $paymentAmount = $totalAmount->minus($totalWithheldAmount);

            } else {
                // For direct payments, use provided values
                $paymentAmount = $dto->amount;
                $partnerId = $dto->paid_to_from_partner_id;
                $paymentType = $dto->payment_type;
            }

            // Create the parent Payment record
            $payment = Payment::create([
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'payment_date' => $dto->payment_date,

                'payment_method' => $dto->payment_method,
                'reference' => $dto->reference,
                'amount' => $paymentAmount, // Net Amount (Gross - Tax)
                'payment_type' => $paymentType,
                'paid_to_from_partner_id' => $partnerId,
                'status' => PaymentStatus::Draft,
            ]);

            // Create WHT Entries linked to Payment
            foreach ($whtEntriesToCreate as $whtData) {
                $whtData['payment_id'] = $payment->id;
                \Modules\Accounting\Models\WithholdingTaxEntry::create($whtData);
            }

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
