<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use App\Enums\Payments\PaymentType;
use Brick\Money\Money;
use InvalidArgumentException;

class CreateJournalEntryForPaymentAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction
    ) {
    }

    public function execute(Payment $payment, User $user): JournalEntry
    {
        $company = $payment->company->load('currency');
        $baseCurrency = $company->currency;
        $paymentCurrency = $payment->currency;

        // Determine the exchange rate. If it's the same currency, the rate is 1.
        $exchangeRate = ($baseCurrency->id === $paymentCurrency->id) ? 1.0 : $paymentCurrency->exchange_rate;

        // 1. Determine the correct accounts based on accounting rules.
        $bankAccountId = $payment->journal->default_debit_account_id;
        if (!$bankAccountId) {
            throw new InvalidArgumentException('The payment journal is not configured with a default bank account.');
        }

        $lines = [];
        $zeroAmount = Money::zero($baseCurrency->code);
        $amountInPaymentCurrency = $payment->amount->getAmount();
        $amountInBaseCurrency = $amountInPaymentCurrency->multipliedBy($exchangeRate);
        $paymentAmountInBase = Money::of($amountInBaseCurrency, $baseCurrency->code, null, \Brick\Math\RoundingMode::HALF_UP);

        \Illuminate\Support\Facades\Log::info('CreateJournalEntryForPaymentAction', [
            'payment_amount' => $payment->amount->getAmount()->toFloat(),
            'exchange_rate' => $exchangeRate,
            'amount_in_base_currency' => $amountInBaseCurrency->toFloat(),
            'payment_amount_in_base_minor' => $paymentAmountInBase->getMinorAmount()->toInt(),
        ]);

        if ($payment->payment_type === PaymentType::Inbound) {
            $arAccountId = $company->default_accounts_receivable_id;
            if (!$arAccountId) {
                throw new \RuntimeException('Default Accounts Receivable is not configured for this company.');
            }
            // Rule: Inbound payment DEBITS the bank, CREDITS Accounts Receivable.
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $bankAccountId,
                debit: $paymentAmountInBase,
                credit: $zeroAmount,
                description: null,
                partner_id: null,
                analytic_account_id: null,
            );
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $arAccountId,
                debit: $zeroAmount,
                credit: $paymentAmountInBase,
                description: null,
                partner_id: null,
                analytic_account_id: null,
            );
        } elseif ($payment->payment_type === PaymentType::Outbound) {
            $apAccountId = $company->default_accounts_payable_id;
            if (!$apAccountId) {
                throw new \RuntimeException('Default Accounts Payable is not configured for this company.');
            }
            // Rule: Outbound payment DEBITS Accounts Payable, CREDITS the bank.
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $apAccountId,
                debit: $paymentAmountInBase,
                credit: $zeroAmount,
                description: null,
                partner_id: null,
                analytic_account_id: null,
            );
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $bankAccountId,
                debit: $zeroAmount,
                credit: $paymentAmountInBase,
                description: null,
                partner_id: null,
                analytic_account_id: null,
            );
        }

        // 2. Create the parent JournalEntry record.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $payment->company_id,
            journal_id: $payment->journal_id,
            currency_id: $baseCurrency->id, // Journal Entry is always in the company's base currency
            entry_date: $payment->payment_date,
            reference: 'Payment #' . $payment->id,
            description: 'Payment from/to ' . $payment->partner->name,
            created_by_user_id: $user->id,
            is_posted: true, // Journal entries for payments are posted immediately.
            lines: $lines,
            source_type: get_class($payment),
            source_id: $payment->id,
        );

        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }
}
