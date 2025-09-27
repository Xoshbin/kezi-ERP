<?php

namespace Modules\Accounting\Services;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\BankStatementLine;
use Modules\Payment\Models\Payment;
use RuntimeException;

// 1. Import the new action

class BankReconciliationService
{
    public function __construct(
        private CurrencyConverterService $currencyConverter
    ) {}

    public function reconcilePayment(Payment $payment, BankStatementLine $statementLine, User $user): void
    {
        // Check if reconciliation is enabled for the company
        $this->validateReconciliationEnabled($payment->company);

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

    /**
     * @param  array<int>  $bankStatementLineIds
     * @param  array<int>  $paymentIds
     */
    public function reconcile(array $bankStatementLineIds, array $paymentIds, User $user): void
    {
        // Fetch all necessary models upfront
        $lines = BankStatementLine::whereIn('id', $bankStatementLineIds)->get();
        $payments = Payment::whereIn('id', $paymentIds)->with('company')->get();

        // Check if reconciliation is enabled for the company
        if ($payments->isNotEmpty()) {
            $this->validateReconciliationEnabled($payments->first()->company);
        } elseif ($lines->isNotEmpty()) {
            $this->validateReconciliationEnabled($lines->first()->bankStatement->company);
        }

        if ($lines->isEmpty() && $payments->isEmpty()) {
            throw new InvalidArgumentException('No items selected for reconciliation.');
        }

        // **THIS IS THE CRITICAL NEW VALIDATION STEP**
        // Before starting the transaction, check if all payments can be reconciled.
        foreach ($payments as $payment) {
            $company = $payment->company;
            if (! $company->default_bank_account_id || ! $company->default_outstanding_receipts_account_id) {
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
        $dto = new CreateJournalEntryForStatementLineDTO(
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
     * @param  array<int>  $bankLineIds  Array of BankStatementLine IDs
     * @param  array<int>  $paymentIds  Array of Payment IDs
     * @param  User  $user  The user performing the reconciliation
     *
     * @throws RuntimeException If totals don't match
     */
    public function reconcileMultiple(array $bankLineIds, array $paymentIds, User $user): void
    {
        // Pre-fetch to check reconciliation setting
        $bankLines = BankStatementLine::whereIn('id', $bankLineIds)->with('bankStatement.company')->get();
        $payments = Payment::whereIn('id', $paymentIds)->with(['currency', 'company'])->get();

        // Check if reconciliation is enabled for the company
        if ($payments->isNotEmpty()) {
            $this->validateReconciliationEnabled($payments->first()->company);
        } elseif ($bankLines->isNotEmpty()) {
            $this->validateReconciliationEnabled($bankLines->first()->bankStatement->company);
        }

        DB::transaction(function () use ($bankLineIds, $paymentIds, $user) {
            $bankLines = BankStatementLine::whereIn('id', $bankLineIds)->with('bankStatement.currency')->get();
            $payments = Payment::whereIn('id', $paymentIds)->with(['currency', 'company'])->get();

            // Validate that totals match using proper Money arithmetic
            $firstBankLine = $bankLines->first();
            if (! $firstBankLine) {
                throw new Exception('No bank lines provided for reconciliation');
            }
            $currency = $firstBankLine->bankStatement->currency;
            $bankTotal = Money::of(0, $currency->code);
            foreach ($bankLines as $line) {
                $bankTotal = $bankTotal->plus($line->amount);
            }

            $paymentTotal = Money::of(0, $currency->code);
            foreach ($payments as $payment) {
                $amount = $payment->amount;
                // Convert to bank statement currency if needed
                if ($payment->currency->code !== $currency->code) {
                    $amount = $this->currencyConverter->convert(
                        $payment->amount,
                        $currency,
                        $payment->payment_date,
                        $payment->company
                    );
                }

                $amount = $payment->payment_type === PaymentType::Inbound ? $amount : $amount->negated();
                $paymentTotal = $paymentTotal->plus($amount);
            }

            if (! $bankTotal->isEqualTo($paymentTotal)) {
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
     * @return Collection<int, BankStatementLine>
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
     * @return Collection<int, Payment>
     */
    public function getUnreconciledPayments(int $companyId)
    {
        return Payment::where('company_id', $companyId)
            ->where('status', PaymentStatus::Confirmed)
            ->whereDoesntHave('bankStatementLines')
            ->with(['partner', 'currency'])
            ->get();
    }

    /**
     * Validate that reconciliation is enabled for the given company.
     *
     * @throws ReconciliationDisabledException
     */
    private function validateReconciliationEnabled(Company $company): void
    {
        if (! $company->enable_reconciliation) {
            throw new ReconciliationDisabledException;
        }
    }
}
