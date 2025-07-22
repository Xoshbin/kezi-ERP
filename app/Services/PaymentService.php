<?php
namespace App\Services;

use App\Events\PaymentConfirmed;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Create and confirm a payment in a single, transactional operation.
     */
    public function createAndConfirm(array $data, User $user): Payment
    {
        return DB::transaction(function () use ($data, $user) {
            // Create the payment record itself.
            $payment = Payment::create($data + [
                    'status' => 'Confirmed',
                    'created_by_user_id' => $user->id, // Ensure user is recorded
                ]);

            // Create the corresponding journal entry.
            $journalEntry = $this->createJournalEntryForPayment($payment, $user);
            $payment->journal_entry_id = $journalEntry->id;
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
        $lines = [];
        $bankAccountId = config('accounting.defaults.default_bank_account_id');

        if ($payment->payment_type === 'Inbound') {
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
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'created_by_user_id' => $user->id,
            'lines' => $lines,
        ];

        return (new JournalEntryService())->create($journalEntryData);
    }
}
