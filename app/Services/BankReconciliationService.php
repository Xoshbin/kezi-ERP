<?php

namespace App\Services;

use App\Actions\Accounting\CreateJournalEntryForReconciliationAction; // 1. Import the new action
use App\Models\BankStatementLine;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    // 2. The JournalEntryService dependency is no longer needed.
    public function __construct() {}

    public function reconcilePayment(Payment $payment, BankStatementLine $statementLine, User $user): void
    {
        DB::transaction(function () use ($payment, $statementLine, $user) {
            // Update the status of the payment and the statement line.
            $payment->status = 'Reconciled';
            $payment->save();

            $statementLine->is_reconciled = true;
            $statementLine->payment_id = $payment->id;
            $statementLine->save();

            // 3. Create and execute the new, dedicated action.
            (new CreateJournalEntryForReconciliationAction())->execute($payment, $user);
        });
    }

    public function reconcile(array $bankStatementLineIds, array $paymentIds, User $user): void
    {
        DB::transaction(function () use ($bankStatementLineIds, $paymentIds, $user) {
            // 1. Fetch and lock the records to prevent race conditions.
            $lines = BankStatementLine::whereIn('id', $bankStatementLineIds)->lockForUpdate()->get();
            $payments = Payment::whereIn('id', $paymentIds)->lockForUpdate()->get();

            // 2. Validate: Ensure totals match and records are in the correct state.
            // (Throw an exception if validation fails).

            // 3. Update the Bank Statement Lines.
            foreach ($lines as $line) {
                $line->is_reconciled = true;
                // For a simple 1-to-1 match, you can link the payment.
                if (count($payments) === 1) {
                    $line->payment_id = $payments->first()->id;
                }
                $line->save();
            }

            // 4. Update the Payments and trigger their final Journal Entries.
            foreach ($payments as $payment) {
                $payment->status = Payment::STATUS_RECONCILED;
                $payment->save();

                // 5. Call the action to create the final JE.
                (new CreateJournalEntryForReconciliationAction())->execute($payment, $user);
            }
        });
    }
}
