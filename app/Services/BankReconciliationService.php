<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;
use App\Models\Account;
use App\Models\Payment;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use InvalidArgumentException;
use App\Models\BankStatementLine;
use Illuminate\Support\Facades\DB;
use App\Actions\Accounting\CreateJournalEntryForStatementLineAction;
use App\Actions\Accounting\CreateJournalEntryForReconciliationAction; // 1. Import the new action

class BankReconciliationService
{
    // 2. The JournalEntryService dependency is no longer needed.
    public function __construct() {}

    public function reconcilePayment(Payment $payment, BankStatementLine $statementLine, User $user): void
    {
        DB::transaction(function () use ($payment, $statementLine, $user) {
            // Update the status of the payment and the statement line.
            $payment->status = PaymentStatus::Reconciled;
            $payment->save();

            $statementLine->is_reconciled = true;
            $statementLine->payment_id = $payment->id;
            $statementLine->save();

            // 3. Create and execute the new, dedicated action.
            (app(CreateJournalEntryForReconciliationAction::class))->execute($payment, $user);
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
                $payment->status = PaymentStatus::Reconciled;
                $payment->save();

                (app(CreateJournalEntryForReconciliationAction::class))->execute($payment, $user);
            }
        });
    }

    public function createWriteOff(BankStatementLine $line, Account $writeOffAccount, User $user, string $description): void
    {
        // Create DTO and execute action - the action handles its own transaction
        $dto = new \App\DataTransferObjects\Accounting\CreateJournalEntryForStatementLineDTO(
            bankStatementLine: $line,
            writeOffAccount: $writeOffAccount,
            user: $user,
            description: $description
        );

        (app(CreateJournalEntryForStatementLineAction::class))->execute($dto);
    }

    /**
     * Reconcile multiple bank statement lines with multiple payments
     *
     * @param array $bankLineIds Array of BankStatementLine IDs
     * @param array $paymentIds Array of Payment IDs
     * @param User $user The user performing the reconciliation
     * @throws RuntimeException If totals don't match
     */
    public function reconcileMultiple(array $bankLineIds, array $paymentIds, User $user): void
    {
        DB::transaction(function () use ($bankLineIds, $paymentIds, $user) {
            $bankLines = BankStatementLine::whereIn('id', $bankLineIds)->get();
            $payments = Payment::whereIn('id', $paymentIds)->get();

            // Validate that totals match using proper Money arithmetic
            $currency = $bankLines->first()->bankStatement->currency;
            $bankTotal = \Brick\Money\Money::of(0, $currency->code);
            foreach ($bankLines as $line) {
                $bankTotal = $bankTotal->plus($line->amount);
            }

            $paymentTotal = \Brick\Money\Money::of(0, $currency->code);
            foreach ($payments as $payment) {
                $amount = $payment->amount;
                // Convert to bank statement currency if needed
                if ($payment->currency->code !== $currency->code) {
                    $amount = \Brick\Money\Money::of($payment->amount->getAmount()->toFloat(), $currency->code);
                }

                $amount = $payment->payment_type === PaymentType::Inbound ? $amount : $amount->negated();
                $paymentTotal = $paymentTotal->plus($amount);
            }

            if (!$bankTotal->isEqualTo($paymentTotal)) {
                throw new RuntimeException('Bank statement lines total does not match payments total');
            }

            // Update all bank statement lines
            foreach ($bankLines as $line) {
                $line->update(['is_reconciled' => true]);
            }

            // Update all payments and create reconciliation journal entries
            foreach ($payments as $payment) {
                $payment->update(['status' => PaymentStatus::Reconciled]);

                // Create reconciliation journal entry for each payment
                (app(CreateJournalEntryForReconciliationAction::class))->execute($payment, $user);
            }

            // Link the first payment to the first bank line for reference
            // In a more sophisticated system, you might want to track all relationships
            if ($bankLines->isNotEmpty() && $payments->isNotEmpty()) {
                $bankLines->first()->update(['payment_id' => $payments->first()->id]);
            }
        });
    }

    /**
     * Get unreconciled bank statement lines for a given bank statement
     *
     * @param int $bankStatementId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnreconciledBankLines(int $bankStatementId)
    {
        return BankStatementLine::where('bank_statement_id', $bankStatementId)
            ->where('is_reconciled', false)
            ->get();
    }

    /**
     * Get unreconciled payments for a given company
     *
     * @param int $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnreconciledPayments(int $companyId)
    {
        return Payment::where('company_id', $companyId)
            ->where('status', PaymentStatus::Confirmed)
            ->whereDoesntHave('bankStatementLines')
            ->with(['partner', 'currency'])
            ->get();
    }
}
