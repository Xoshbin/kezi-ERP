<?php

namespace App\Services;

use App\Events\PaymentConfirmed;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(protected JournalEntryService $journalEntryService)
    {
    }

    /**
     * Create a new draft payment.
     */
    public function create(array $data, User $user): Payment
    {
        return Payment::create($data + [
            'status' => Payment::STATUS_DRAFT,
            'created_by_user_id' => $user->id,
        ]);
    }

    /**
     * Confirm a draft payment, locking it and creating the journal entry.
     */
    public function confirm(Payment $payment, User $user): Payment
    {
        if ($payment->status !== Payment::STATUS_DRAFT) {
            throw new UpdateNotAllowedException('Only draft payments can be confirmed.');
        }

        return DB::transaction(function () use ($payment, $user) {
            // Create the corresponding journal entry.
            $journalEntry = $this->createJournalEntryForPayment($payment, $user);

            $payment->journal_entry_id = $journalEntry->id;
            $payment->status = Payment::STATUS_CONFIRMED;
            $payment->save();

            PaymentConfirmed::dispatch($payment);

            return $payment;
        });
    }

    /**
     * Creates the double-entry journal entry for a confirmed payment.
     */
    private function createJournalEntryForPayment(Payment $payment, User $user): JournalEntry
    {
        if (!$payment->journal_id) {
            throw new InvalidArgumentException('The payment must have a journal to be confirmed.');
        }

        $lines = [];
        // The Journal is the source of truth for which account to use.
        // Eager load the relationship to prevent extra queries.
        $payment->load('journal');
        $debitAccountId = $payment->journal->default_debit_account_id;
        $creditAccountId = $payment->journal->default_credit_account_id;

        if (!$debitAccountId || !$creditAccountId) {
            throw new InvalidArgumentException('The selected journal is not fully configured with default debit and credit accounts.');
        }

        if ($payment->payment_type === Payment::TYPE_INBOUND) {
            // Inbound: Money comes IN to the bank (debit), reducing customer debt (credit).
            $arAccountId = config('accounting.defaults.accounts_receivable_id');
            $lines[] = ['account_id' => $debitAccountId, 'debit' => $payment->amount, 'credit' => 0];
            $lines[] = ['account_id' => $arAccountId, 'credit' => $payment->amount, 'debit' => 0];
        } else { // Outbound
            // Outbound: Money goes OUT of the bank (credit), reducing company debt (debit).
            $apAccountId = config('accounting.defaults.accounts_payable_id');
            $lines[] = ['account_id' => $apAccountId, 'debit' => $payment->amount, 'credit' => 0];
            $lines[] = ['account_id' => $creditAccountId, 'credit' => $payment->amount, 'debit' => 0];
        }

        $journalEntryData = [
            'company_id' => $payment->company_id,
            'journal_id' => $payment->journal_id,
            'entry_date' => $payment->payment_date,
            'reference' => 'Payment #' . $payment->id,
            'description' => 'Payment from/to ' . $payment->partner->name,
            'source_type' => get_class($payment),
            'source_id' => $payment->id,
            'created_by_user_id' => $user->id,
            'lines' => $lines,
        ];

        Log::debug('PaymentService: Creating journal entry with data:', $journalEntryData);

        return $this->journalEntryService->create($journalEntryData, true);
    }

    /**
     * Update a draft payment. Confirmed payments are immutable.
     */
    public function update(Payment $payment, array $data): bool
    {
        // Guard Clause: Never allow updating a confirmed payment.
        if ($payment->status === Payment::STATUS_CONFIRMED) {
            throw new UpdateNotAllowedException('Cannot modify a confirmed payment.');
        }

        return $payment->update($data);
    }
}
