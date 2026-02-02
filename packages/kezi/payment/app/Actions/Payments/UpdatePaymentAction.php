<?php

namespace Kezi\Payment\Actions\Payments;

use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Kezi\Foundation\Models\Currency;
use Kezi\Payment\DataTransferObjects\Payments\UpdatePaymentDTO;
use Kezi\Payment\Enums\Payments\PaymentStatus;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Payment\Models\Payment;
use Kezi\Payment\Services\Payments\Strategies\SettlementStrategy;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Sales\Models\Invoice;

class UpdatePaymentAction
{
    public function __construct(
        private readonly \Kezi\Accounting\Services\Accounting\LockDateService $lockDateService,
        private readonly \Kezi\Foundation\Services\CurrencyConverterService $currencyConverter
    ) {}

    public function execute(UpdatePaymentDTO $dto): Payment
    {
        $payment = $dto->payment;

        if ($payment->status !== PaymentStatus::Draft) {
            throw new \Kezi\Foundation\Exceptions\UpdateNotAllowedException('Only draft payments can be updated.');
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
            $company = \App\Models\Company::findOrFail($dto->company_id);
            $currency = Currency::findOrFail($dto->currency_id);
            $currencyCode = $currency->code;

            // Get exchange rate for WHT base amount conversion
            $exchangeRate = 1.0;
            if ($currency->id !== $company->currency_id) {
                $exchangeRate = $this->currencyConverter->getExchangeRate(
                    $currency,
                    Carbon::parse($dto->payment_date),
                    $company
                ) ?? 1.0;
            }

            // Determine payment details based on presence of document links
            if (! empty($dto->document_links)) {
                // For settlement payments, calculate from document links
                $totalAmount = Money::of(0, $currencyCode);
                $totalWithheldAmount = Money::of(0, $currencyCode);
                $documentTypes = [];
                $partnerId = null;
                $whtEntriesToCreate = [];

                foreach ($dto->document_links as $link) {
                    $totalAmount = $totalAmount->plus($link->amount_applied);
                    $documentTypes[$link->document_type] = true;

                    // Get Document Partner to check for WHT
                    $documentPartner = null;
                    if ($link->document_type === 'invoice') {
                        $invoice = Invoice::findOrFail($link->document_id);
                        $documentPartner = $invoice->customer;
                        $partnerId = $partnerId ?: $invoice->customer_id;
                    } elseif ($link->document_type === 'vendor_bill') {
                        $bill = VendorBill::findOrFail($link->document_id);
                        $documentPartner = $bill->vendor;
                        $partnerId = $partnerId ?: $bill->vendor_id;

                        // WHT Logic: Currently only for Vendor Bills (Outbound)
                        if ($documentPartner && $documentPartner->withholding_tax_type_id) {
                            $whtType = $documentPartner->withholdingTaxType;
                            $taxAmount = $whtType->calculateWithholding($link->amount_applied);
                            $totalWithheldAmount = $totalWithheldAmount->plus($taxAmount);

                            // Convert amounts to Base Currency for Storage
                            $taxAmountBase = $this->currencyConverter->convertWithRate($taxAmount, $exchangeRate, $company->currency->code);
                            $linkAmountBase = $this->currencyConverter->convertWithRate($link->amount_applied, $exchangeRate, $company->currency->code);

                            $whtEntriesToCreate[] = [
                                'company_id' => $dto->company_id,
                                'withholding_tax_type_id' => $whtType->id,
                                'vendor_id' => $documentPartner->id,
                                'base_amount' => $linkAmountBase,
                                'withheld_amount' => $taxAmountBase,
                                'rate_applied' => $whtType->rate,
                                'currency_id' => $dto->currency_id,
                            ];
                        }
                    }
                }

                if (count($documentTypes) > 1) {
                    throw new InvalidArgumentException('A payment cannot be linked to both invoices and vendor bills simultaneously.');
                }
                $paymentType = key($documentTypes) === 'invoice' ? PaymentType::Inbound : PaymentType::Outbound;

                // Adjust payment amount (Net Amount)
                $paymentAmountValue = $totalAmount->minus($totalWithheldAmount);
            } else {
                // For direct payments, use provided values
                $paymentAmountValue = $dto->amount;
                $partnerId = $dto->paid_to_from_partner_id;
                $paymentType = $dto->payment_type;
                $whtEntriesToCreate = [];
            }

            // Update the parent Payment record
            $payment->update([
                'company_id' => $dto->company_id,
                'journal_id' => $dto->journal_id,
                'currency_id' => $dto->currency_id,
                'payment_date' => $dto->payment_date,

                'payment_method' => $dto->payment_method,
                'reference' => $dto->reference,
                'amount' => $paymentAmountValue,
                'payment_type' => $paymentType,
                'paid_to_from_partner_id' => $partnerId,
            ]);

            // Clear existing WHT entries and recreate
            \Kezi\Accounting\Models\WithholdingTaxEntry::where('payment_id', $payment->id)->delete();
            foreach ($whtEntriesToCreate as $whtData) {
                $whtData['payment_id'] = $payment->id;
                \Kezi\Accounting\Models\WithholdingTaxEntry::create($whtData);
            }

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
