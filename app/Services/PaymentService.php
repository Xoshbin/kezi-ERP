<?php

namespace App\Services;

use App\Events\PaymentConfirmed;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
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
        $bankAccountId = config('accounting.defaults.default_bank_account_id');

        if ($payment->payment_type === Payment::TYPE_INBOUND) {
            // Inbound: Money comes IN to the bank, reducing customer debt.
            $arAccountId = config('accounting.defaults.accounts_receivable_id');
            $lines[] = ['account_id' => $bankAccountId, 'debit' => $payment->amount];
            $lines[] = ['account_id' => $arAccountId, 'credit' => $payment->amount];
        } else { // Outbound
            // Outbound: Money goes OUT of the bank, reducing company debt.
            $apAccountId = config('accounting.defaults.accounts_payable_id');
            $lines[] = ['account_id' => $apAccountId, 'debit' => $payment->amount];
            $lines[] = ['account_id' => $bankAccountId, 'credit' => $payment->amount];
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

        return (new JournalEntryService())->create($journalEntryData);
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
