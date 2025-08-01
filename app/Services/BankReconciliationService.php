<?php

namespace App\Services;

use App\Actions\Accounting\CreateJournalEntryForReconciliationAction; // 1. Import the new action
use App\Models\BankStatementLine;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

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
        // Fetch all necessary models upfront
        $lines = BankStatementLine::whereIn('id', $bankStatementLineIds)->get();
        $payments = Payment::whereIn('id', $paymentIds)->with('company')->get();

        if ($lines->isEmpty() && $payments->isEmpty()) {
            throw new InvalidArgumentException('No items selected for reconciliation.');
        }

        // **THIS IS THE CRITICAL NEW VALIDATION STEP**
        // Before starting the transaction, check if all payments can be reconciled.
        foreach ($payments as $payment) {
            $company = $payment->company;
            if (!$company->default_bank_account_id || !$company->default_outstanding_receipts_account_id) {
                throw new RuntimeException("Company '{$company->name}' is missing default bank or outstanding accounts configuration.");
            }
        }

        // Now, proceed with the transaction, confident that the final step will succeed.
        DB::transaction(function () use ($lines, $payments, $user) {
            foreach ($lines as $line) {
                $line->is_reconciled = true;
                $line->save();
            }

            foreach ($payments as $payment) {
                $payment->status = Payment::STATUS_RECONCILED;
                $payment->save();

                (new CreateJournalEntryForReconciliationAction())->execute($payment, $user);
            }
        });
    }
}
