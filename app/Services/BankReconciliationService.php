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
    public function __construct()
    {
    }

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
}
