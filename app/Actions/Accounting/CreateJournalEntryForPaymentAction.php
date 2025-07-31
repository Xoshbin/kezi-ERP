<?php

namespace App\Actions\Accounting;

use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateJournalEntryForPaymentAction
{
    public function execute(Payment $payment, User $user): JournalEntry
    {
        return DB::transaction(function () use ($payment, $user) {
            $company = $payment->company;
            $currency = $payment->currency;
            $currencyCode = $currency->code;

            // 1. Determine the correct accounts based on accounting rules.
            $bankAccountId = $payment->journal->default_debit_account_id;
            if (!$bankAccountId) {
                throw new InvalidArgumentException('The payment journal is not configured with a default bank account.');
            }

            $lines = [];
            $zeroAmount = Money::of(0, $currencyCode);

            if ($payment->payment_type === Payment::TYPE_INBOUND) {
                $arAccountId = $company->default_accounts_receivable_id;
                if (!$arAccountId) {
                    throw new \RuntimeException('Default Accounts Receivable is not configured for this company.');
                }
                // Rule: Inbound payment DEBITS the bank, CREDITS Accounts Receivable.
                $lines[] = ['account_id' => $bankAccountId, 'debit' => $payment->amount, 'credit' => $zeroAmount];
                $lines[] = ['account_id' => $arAccountId, 'debit' => $zeroAmount, 'credit' => $payment->amount];

            } elseif ($payment->payment_type === Payment::TYPE_OUTBOUND) {
                $apAccountId = $company->default_accounts_payable_id;
                if (!$apAccountId) {
                    throw new \RuntimeException('Default Accounts Payable is not configured for this company.');
                }
                // Rule: Outbound payment DEBITS Accounts Payable, CREDITS the bank.
                $lines[] = ['account_id' => $apAccountId, 'debit' => $payment->amount, 'credit' => $zeroAmount];
                $lines[] = ['account_id' => $bankAccountId, 'debit' => $zeroAmount, 'credit' => $payment->amount];
            }

            // 2. Create the parent JournalEntry record.
            $journalEntry = JournalEntry::create([
                'company_id' => $payment->company_id,
                'journal_id' => $payment->journal_id,
                'currency_id' => $payment->currency_id,
                'entry_date' => $payment->payment_date,
                'reference' => 'Payment #' . $payment->id,
                'description' => 'Payment from/to ' . $payment->partner->name,
                'source_type' => get_class($payment),
                'source_id' => $payment->id,
                'created_by_user_id' => $user->id,
                'total_debit' => $payment->amount,
                'total_credit' => $payment->amount,
                'is_posted' => true, // Journal entries for payments are posted immediately.
            ]);

            // 3. Add the required currency_id to each line for the MoneyCast.
            $linesToCreate = array_map(fn($line) => $line + ['currency_id' => $currency->id], $lines);

            // 4. Create the lines.
            $journalEntry->lines()->createMany($linesToCreate);

            return $journalEntry;
        });
    }
}
