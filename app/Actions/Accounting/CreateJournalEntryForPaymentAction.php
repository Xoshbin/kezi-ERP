<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use App\Enums\Payments\PaymentType;
use App\Services\CurrencyConverterService;
use InvalidArgumentException;

class CreateJournalEntryForPaymentAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly CurrencyConverterService $currencyConverter
    ) {
    }

    public function execute(Payment $payment, User $user): JournalEntry
    {
        $company = $payment->company->load('currency');

        // Load the partner to access individual accounts
        $payment->load('partner');

        // Use CurrencyConverterService for all currency conversion logic
        $conversion = $this->currencyConverter->convertToCompanyBaseCurrency(
            $payment->amount,
            $payment->currency,
            $company
        );

        // 1. Determine the correct accounts based on accounting rules.
        $bankAccountId = $payment->journal->default_debit_account_id;
        if (!$bankAccountId) {
            throw new InvalidArgumentException('The payment journal is not configured with a default bank account.');
        }

        $lines = [];
        $zeroAmount = $conversion->createZeroInTargetCurrency();

        \Illuminate\Support\Facades\Log::info('CreateJournalEntryForPaymentAction', [
            'payment_amount' => $payment->amount->getAmount()->toFloat(),
            'exchange_rate' => $conversion->exchangeRate,
            'amount_in_base_currency' => $conversion->convertedAmount->getAmount()->toFloat(),
            'payment_amount_in_base_minor' => $conversion->convertedAmount->getMinorAmount()->toInt(),
        ]);

        if ($payment->payment_type === PaymentType::Inbound) {
            // Use partner's individual receivable account if available, otherwise fall back to default
            $arAccountId = $payment->partner->receivable_account_id ?? $company->default_accounts_receivable_id;
            if (!$arAccountId) {
                throw new \RuntimeException('Default Accounts Receivable is not configured for this company.');
            }
            // Rule: Inbound payment DEBITS the bank, CREDITS Accounts Receivable.
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $bankAccountId,
                debit: $conversion->convertedAmount,
                credit: $zeroAmount,
                description: null,
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $conversion->originalAmount,
                original_currency_id: $conversion->originalCurrency->id,
                exchange_rate_at_transaction: $conversion->exchangeRate,
            );
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $arAccountId,
                debit: $zeroAmount,
                credit: $conversion->convertedAmount,
                description: null,
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $conversion->originalAmount,
                original_currency_id: $conversion->originalCurrency->id,
                exchange_rate_at_transaction: $conversion->exchangeRate,
            );
        } elseif ($payment->payment_type === PaymentType::Outbound) {
            // Use partner's individual payable account if available, otherwise fall back to default
            $apAccountId = $payment->partner->payable_account_id ?? $company->default_accounts_payable_id;
            if (!$apAccountId) {
                throw new \RuntimeException('Default Accounts Payable is not configured for this company.');
            }
            // Rule: Outbound payment DEBITS Accounts Payable, CREDITS the bank.
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $apAccountId,
                debit: $conversion->convertedAmount,
                credit: $zeroAmount,
                description: null,
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $conversion->originalAmount,
                original_currency_id: $conversion->originalCurrency->id,
                exchange_rate_at_transaction: $conversion->exchangeRate,
            );
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $bankAccountId,
                debit: $zeroAmount,
                credit: $conversion->convertedAmount,
                description: null,
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $conversion->originalAmount,
                original_currency_id: $conversion->originalCurrency->id,
                exchange_rate_at_transaction: $conversion->exchangeRate,
            );
        }

        // 2. Create the parent JournalEntry record.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $payment->company_id,
            journal_id: $payment->journal_id,
            currency_id: $conversion->targetCurrency->id, // Journal Entry is always in the company's base currency
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
