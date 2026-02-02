<?php

namespace Kezi\Accounting\Actions\Accounting;

use App\Models\User;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Payment\Models\Payment;
use RuntimeException;

class CreateJournalEntryForPaymentAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
    ) {}

    public function execute(Payment $payment, User $user): JournalEntry
    {
        $company = $payment->company->load('currency');
        $baseCurrency = $company->currency;
        $paymentCurrency = $payment->currency;

        // Load the partner to access individual accounts
        $payment->load('partner');

        // Determine the exchange rate. If it's the same currency, the rate is 1.
        if ($baseCurrency->id === $paymentCurrency->id) {
            $exchangeRate = 1.0;
        } else {
            // Use the exchange rate stored in the payment (set during payment creation/confirmation)
            $exchangeRate = $payment->exchange_rate_at_payment ?? 1.0;
        }

        // 1. Determine the correct accounts based on accounting rules.
        $bankAccountId = $payment->journal->default_debit_account_id;
        if (! $bankAccountId) {
            throw new InvalidArgumentException('The payment journal is not configured with a default bank account.');
        }

        $lines = [];
        $zeroAmount = Money::zero($baseCurrency->code);
        $amountInPaymentCurrency = $payment->amount->getAmount();
        $amountInBaseCurrency = $amountInPaymentCurrency->multipliedBy($exchangeRate);
        $paymentAmountInBase = Money::of($amountInBaseCurrency, $baseCurrency->code, null, RoundingMode::HALF_UP);

        Log::info('CreateJournalEntryForPaymentAction', [
            'payment_amount' => $payment->amount->getAmount()->toFloat(),
            'exchange_rate' => $exchangeRate,
            'amount_in_base_currency' => $amountInBaseCurrency->toFloat(),
            'payment_amount_in_base_minor' => $paymentAmountInBase->getMinorAmount()->toInt(),
        ]);

        // Determine the counterpart account based on whether the payment has document links (settlement) or not (standalone)
        $whtEntries = $payment->withholdingTaxEntries()->with('withholdingTaxType')->get();
        // Calculate Total WHT in Base currency (values are stored in base currency)
        $totalWhtInBase = Money::of(0, $baseCurrency->code);
        foreach ($whtEntries as $entry) {
            $totalWhtInBase = $totalWhtInBase->plus($entry->withheld_amount);
        }

        $grossAmountInBase = $paymentAmountInBase->plus($totalWhtInBase);

        if ($payment->paymentDocumentLinks()->exists()) {
            // For settlement payments, use AR/AP accounts
            if ($payment->payment_type === PaymentType::Inbound) {
                // Use partner's individual receivable account if available, otherwise fall back to default
                $counterpartAccountId = $payment->partner->receivable_account_id ?? $company->default_accounts_receivable_id;
                if (! $counterpartAccountId) {
                    throw new RuntimeException('Default Accounts Receivable is not configured for this company.');
                }
                // Rule: Inbound payment DEBITS the bank, CREDITS Accounts Receivable.
                // With WHT: DEBIT Bank (Net), DEBIT WHT Receivable (Tax), CREDIT AR (Gross)
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $bankAccountId,
                    debit: $paymentAmountInBase,
                    credit: $zeroAmount,
                    description: null,
                    partner_id: null,
                    analytic_account_id: null,
                );

                // Add WHT Receivable Lines (Asset)
                foreach ($whtEntries as $entry) {
                    $whtAccount = $entry->withholdingTaxType->withholding_account_id;
                    if (! $whtAccount) {
                        throw new RuntimeException('Withholding Tax account not configured for type: '.$entry->withholdingTaxType->name);
                    }
                    $lines[] = new CreateJournalEntryLineDTO(
                        account_id: $whtAccount,
                        debit: $entry->withheld_amount,
                        credit: $zeroAmount,
                        description: 'WHT Receivable',
                        partner_id: $payment->partner->id, // Track against partner?
                        analytic_account_id: null,
                    );
                }

                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $counterpartAccountId,
                    debit: $zeroAmount,
                    credit: $grossAmountInBase, // Credit Gross to clear invoice
                    description: null,
                    partner_id: $payment->paid_to_from_partner_id,
                    analytic_account_id: null,
                );
            } else { // Outbound
                // Use partner's individual payable account if available, otherwise fall back to default
                $counterpartAccountId = $payment->partner->payable_account_id ?? $company->default_accounts_payable_id;
                if (! $counterpartAccountId) {
                    throw new RuntimeException('Default Accounts Payable is not configured for this company.');
                }
                // Rule: Outbound payment DEBITS Accounts Payable, CREDITS the bank.
                // With WHT: DEBIT AP (Gross), CREDIT Bank (Net), CREDIT WHT Payable (Tax)
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $counterpartAccountId,
                    debit: $grossAmountInBase, // Debit Gross to clear bill
                    credit: $zeroAmount,
                    description: null,
                    partner_id: $payment->paid_to_from_partner_id,
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

                // Add WHT Payable Lines (Liability)
                foreach ($whtEntries as $entry) {
                    $whtAccount = $entry->withholdingTaxType->withholding_account_id;
                    if (! $whtAccount) {
                        throw new RuntimeException('Withholding Tax account not configured for type: '.$entry->withholdingTaxType->name);
                    }
                    $lines[] = new CreateJournalEntryLineDTO(
                        account_id: $whtAccount,
                        debit: $zeroAmount,
                        credit: $entry->withheld_amount,
                        description: 'WHT Payable',
                        partner_id: null, // Liability is company's
                        analytic_account_id: null,
                    );
                }
            }
        } else {
            // No document links: require partner and post as AR/AP advance/credit
            // Standalone payments with WHT? Rare but possible.
            // Logic assumes WHT is only usually on Settlements in this simplified implementation.
            // But if we have entries, apply the same logic.

            if ($payment->paid_to_from_partner_id !== null) {
                if ($payment->payment_type === PaymentType::Inbound) {
                    // Use partner's individual receivable account if available, otherwise fall back to default
                    $counterpartAccountId = $payment->partner->receivable_account_id ?? $company->default_accounts_receivable_id;
                    if (! $counterpartAccountId) {
                        throw new RuntimeException('Default Accounts Receivable is not configured for this company.');
                    }
                    // Inbound payment DEBITS the bank, CREDITS A/R
                    $lines[] = new CreateJournalEntryLineDTO(
                        account_id: $bankAccountId,
                        debit: $paymentAmountInBase,
                        credit: $zeroAmount,
                        description: null,
                        partner_id: null,
                        analytic_account_id: null,
                    );
                    // Add WHT Receivable Lines (Asset)
                    foreach ($whtEntries as $entry) {
                        $whtAccount = $entry->withholdingTaxType->withholding_account_id;
                        $lines[] = new CreateJournalEntryLineDTO(
                            account_id: $whtAccount,
                            debit: $entry->withheld_amount,
                            credit: $zeroAmount,
                            description: 'WHT Receivable',
                            partner_id: $payment->partner->id,
                            analytic_account_id: null,
                        );
                    }

                    $lines[] = new CreateJournalEntryLineDTO(
                        account_id: $counterpartAccountId,
                        debit: $zeroAmount,
                        credit: $grossAmountInBase, // Gross
                        description: null,
                        partner_id: null,
                        analytic_account_id: null,
                    );
                } else { // Outbound
                    // Use partner's individual payable account if available, otherwise fall back to default
                    $counterpartAccountId = $payment->partner->payable_account_id ?? $company->default_accounts_payable_id;
                    if (! $counterpartAccountId) {
                        throw new RuntimeException('Default Accounts Payable is not configured for this company.');
                    }
                    // Outbound payment DEBITS A/P, CREDITS bank
                    $lines[] = new CreateJournalEntryLineDTO(
                        account_id: $counterpartAccountId,
                        debit: $grossAmountInBase, // Gross
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

                    // Add WHT Payable Lines (Liability)
                    foreach ($whtEntries as $entry) {
                        $whtAccount = $entry->withholdingTaxType->withholding_account_id;
                        $lines[] = new CreateJournalEntryLineDTO(
                            account_id: $whtAccount,
                            debit: $zeroAmount,
                            credit: $entry->withheld_amount,
                            description: 'WHT Payable',
                            partner_id: null,
                            analytic_account_id: null,
                        );
                    }
                }
            } else {
                // Truly standalone non-partner payments: require explicit counterpart account
                if ($whtEntries->isNotEmpty()) {
                    throw new RuntimeException('Standalone payments cannot have Withholding Tax entries without a partner.');
                }
                throw new InvalidArgumentException('Standalone non-partner payments must have a counterpart account.');
            }
        }

        // 2. Create the parent JournalEntry record.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $payment->company_id,
            journal_id: $payment->journal_id,
            currency_id: $baseCurrency->id, // Journal Entry is always in the company's base currency
            entry_date: $payment->payment_date,
            reference: 'Payment #'.$payment->id,
            description: 'Payment from/to '.($payment->partner->name ?? 'N/A'),
            created_by_user_id: $user->id,
            is_posted: true, // Journal entries for payments are posted immediately.
            lines: $lines,
            source_type: get_class($payment),
            source_id: $payment->id,
        );

        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }
}
