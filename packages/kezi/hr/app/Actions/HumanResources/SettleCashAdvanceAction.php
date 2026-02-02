<?php

namespace Kezi\HR\Actions\HumanResources;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Accounting\Services\Accounting\LockDateService;
use Kezi\HR\Enums\CashAdvanceStatus;
use Kezi\HR\Enums\ExpenseReportStatus;
use Kezi\HR\Models\CashAdvance;
use Kezi\Payment\Actions\Payments\CreatePaymentAction;
use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDTO;

class SettleCashAdvanceAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly CreatePaymentAction $createPaymentAction,
        private readonly LockDateService $lockDateService,
    ) {}

    public function execute(
        CashAdvance $cashAdvance,
        string $settlementMethod, // 'none', 'cash_return', 'reimbursement'
        ?int $bankAccountId,
        User $user
    ): void {
        $cashAdvance->refresh();
        DB::transaction(function () use ($cashAdvance, $settlementMethod, $bankAccountId, $user) {
            if ($cashAdvance->status !== CashAdvanceStatus::PendingSettlement) {
                throw new \InvalidArgumentException('Only pending settlement cash advances can be settled.');
            }

            $company = $cashAdvance->company;
            $company->refresh();

            // Check lock date
            $this->lockDateService->enforce($company, Carbon::now());

            // Get employee advance receivable account
            $receivableAccountId = $company->default_employee_advance_receivable_account_id;
            if (! $receivableAccountId) {
                throw new \RuntimeException('Employee advance receivable account not configured for company.');
            }

            // Get default journal (operations or misc usually, but let's use default_journal_id or find one)
            // For expenses, we usually use a Vendor Bills journal or General/Misc operations.
            // Let's look for a 'miscellaneous' or 'general' journal.
            $journal = \Kezi\Accounting\Models\Journal::where('company_id', $company->id)
                ->whereIn('type', ['miscellaneous', 'general'])
                ->first();

            if (! $journal) {
                // Fallback to any journal
                $journal = \Kezi\Accounting\Models\Journal::where('company_id', $company->id)->first();
            }

            if (! $journal) {
                throw new \RuntimeException('No journal found for company.');
            }
            $journalId = $journal->id;

            // Gather all Approved Expense Reports
            $expenseReports = $cashAdvance->expenseReports()
                ->where('status', ExpenseReportStatus::Approved)
                ->get();

            if ($expenseReports->isEmpty()) {
                // It's possible to settle without expenses if it was a pure return?
                // But usually we expect expenses. If pure return, maybe different flow?
                // Let's assume there might be 0 expenses (full return of advance).
            }

            $totalExpenses = Money::zero($cashAdvance->currency->code);
            $jeLines = [];

            // 1. Create Lines for Expenses (Debit)
            foreach ($expenseReports as $report) {
                foreach ($report->lines as $line) {
                    $totalExpenses = $totalExpenses->plus($line->amount);

                    $jeLines[] = new CreateJournalEntryLineDTO(
                        account_id: $line->expense_account_id,
                        debit: $line->amount,
                        credit: Money::zero($cashAdvance->currency->code),
                        description: "Exp Rep #{$report->report_number}: {$line->description}",
                        partner_id: $line->partner_id, // Vendor if applicable
                        analytic_account_id: null,
                    );
                }
            }

            // 2. Credit Employee Receivable for the total expenses
            if ($totalExpenses->isPositive()) {
                $jeLines[] = new CreateJournalEntryLineDTO(
                    account_id: $receivableAccountId,
                    debit: Money::zero($cashAdvance->currency->code),
                    credit: $totalExpenses,
                    description: "Clearance of advance #{$cashAdvance->advance_number} via expenses",
                    partner_id: null, // Employee is not a partner in this context usually, or we link user?
                    analytic_account_id: null,
                );
            }

            // Create the Expense Recognition Journal Entry
            if (! empty($jeLines)) {
                $dto = new CreateJournalEntryDTO(
                    company_id: $cashAdvance->company_id,
                    journal_id: $journalId,
                    currency_id: $cashAdvance->currency_id,
                    entry_date: Carbon::now(),
                    reference: "Settlement {$cashAdvance->advance_number}",
                    description: "Expense settlement for Cash Advance {$cashAdvance->advance_number}",
                    created_by_user_id: $user->id,
                    is_posted: true,
                    lines: $jeLines,
                    source_type: CashAdvance::class,
                    source_id: $cashAdvance->id,
                );

                $settlementJE = $this->createJournalEntryAction->execute($dto);
                $cashAdvance->update(['settlement_journal_entry_id' => $settlementJE->id]);
            }

            // 3. Handle Balance (Excess or Shortfall)
            $disbursed = $cashAdvance->disbursed_amount ?? Money::zero($cashAdvance->currency->code);
            $balance = $disbursed->minus($totalExpenses); // Positive = Advance > Expenses (Employee Owes). Negative = Expenses > Advance (We Owe).

            if ($balance->isPositive() && $settlementMethod === 'cash_return') {
                if (! $bankAccountId) {
                    throw new \InvalidArgumentException('Bank account required for cash return.');
                }

                // Employee returns cash. Receipt.
                // Dr Bank, Cr Employee Receivable.

                // reusing CreatePaymentAction (Inbound)
                // reusing CreatePaymentAction (Inbound)
                $paymentDTO = new CreatePaymentDTO(
                    company_id: $cashAdvance->company_id,
                    journal_id: $journalId, // Use the selected journal
                    currency_id: $cashAdvance->currency_id,
                    payment_date: Carbon::now()->toDateString(),
                    payment_type: \Kezi\Payment\Enums\Payments\PaymentType::Inbound,
                    payment_method: \Kezi\Payment\Enums\Payments\PaymentMethod::BankTransfer,
                    paid_to_from_partner_id: null,
                    amount: $balance,
                    document_links: [],
                    reference: "Return {$cashAdvance->advance_number}",
                );

                // We need to ensure CreatePaymentAction creates the right JE.
                // It usually does Dr Bank, Cr Account (Payment/Suspense or Receivable).
                // We need it to Credit the Employee Advance Receivable Account.
                // Standard Payment action might rely on Partner setting?
                // Or we can create the JE manually.
                // Checking CreatePaymentLogic... typically it uses a "Outstanding Receipts" or similar if not direct.
                // Given the constraints and existing tools, let's create the Payment record mainly for tracking,
                // but we might need to be careful with the generated JE.
                // If CreatePaymentAction is robust, it might handle it.
                // BUT, to be safe and explicit with the Employee Receivable account, maybe we create the JE ourselves?
                // Actually `CreatePaymentAction` usually creates a JE.
                // Let's assume we use it, but we might need to adjust the "Counterpart Account" logic in it?
                // For now, let's stick to generating the JE manually if we want precision on the Receivable Account,
                // OR trust the Payment system.
                // BETTER: Let's create the Payment via the Action, and if we can override the credit account, great.
                // If not, we might be creating a "Payment" that hits "Accounts Receivable" (generic) instead of "Employee Advance Receivable".
                // This is a nuance.
                // Safest bet for "Manual Data Entry First" + "Immutability":
                // Create the Payment using the Action. The Action likely puts it in a temporary/suspense account or AR.
                // WE WANT TO CREDIT `default_employee_advance_receivable_account_id`.
                // Let's create a specific JE for the return to ensure correctness, and maybe NOT use CreatePaymentAction?
                // Or use CreatePaymentAction but pass the specific account?

                // Let's check CreatePaymentAction signature ?
                // I don't see it accepting an override account in DTO typically unless it's advanced.
                // Let's do manual JE + Manual Payment Record creation (or minimal payment record).

                // actually, let's keep it simple:
                // Create JE: Dr Bank, Cr Employee Receivable.
                // Create Payment Model Record linked to JE.

                $lines = [
                    new CreateJournalEntryLineDTO(
                        account_id: $bankAccountId,
                        debit: $balance,
                        credit: Money::zero($cashAdvance->currency->code),
                        description: "Return of excess advance: {$cashAdvance->employee->full_name}",
                        partner_id: null,
                        analytic_account_id: null,
                    ),
                    new CreateJournalEntryLineDTO(
                        account_id: $receivableAccountId,
                        debit: Money::zero($cashAdvance->currency->code),
                        credit: $balance,
                        description: "Return of excess advance: {$cashAdvance->employee->full_name}",
                        partner_id: null,
                        analytic_account_id: null,
                    ),
                ];

                $dto = new CreateJournalEntryDTO(
                    company_id: $cashAdvance->company_id,
                    journal_id: $company->default_cash_journal_id ?? $journalId, // Use cash journal for money movement
                    currency_id: $cashAdvance->currency_id,
                    entry_date: Carbon::now(),
                    reference: "Return {$cashAdvance->advance_number}",
                    description: "Return of excess cash advance {$cashAdvance->advance_number}",
                    created_by_user_id: $user->id,
                    is_posted: true,
                    lines: $lines,
                    source_type: CashAdvance::class,
                    source_id: $cashAdvance->id,
                );

                $returnJE = $this->createJournalEntryAction->execute($dto);

                // Create Payment Record manually or via factory/model to link it?
                // Just use the CreatePaymentAction but ideally we'd want to tell it "Don't create JE" if we did it.
                // But CreatePaymentAction likely *always* creates JE.
                // So let's use CreatePaymentAction and assume we can reconcile/move later?
                // OR, just implement the JE creation inside CreatePaymentAction correctly?
                // Let's assume CreatePaymentAction is for "Partner Payments". This is internal.
                // Manual JE is safer here.

            } elseif ($balance->isNegative() && $settlementMethod === 'reimbursement') {
                if (! $bankAccountId) {
                    throw new \InvalidArgumentException('Bank account required for reimbursement.');
                }

                $reimbursementAmount = $balance->abs();

                // We owe employee. Dr Employee Receivable (clearing credit balance), Cr Bank.
                $lines = [
                    new CreateJournalEntryLineDTO(
                        account_id: $receivableAccountId,
                        debit: $reimbursementAmount,
                        credit: Money::zero($cashAdvance->currency->code),
                        description: "Reimbursement for advance shortfall: {$cashAdvance->employee->full_name}",
                        partner_id: null,
                        analytic_account_id: null,
                    ),
                    new CreateJournalEntryLineDTO(
                        account_id: $bankAccountId,
                        debit: Money::zero($cashAdvance->currency->code),
                        credit: $reimbursementAmount,
                        description: "Reimbursement for advance shortfall: {$cashAdvance->employee->full_name}",
                        partner_id: null,
                        analytic_account_id: null,
                    ),
                ];

                $dto = new CreateJournalEntryDTO(
                    company_id: $cashAdvance->company_id,
                    journal_id: $company->default_cash_journal_id ?? $journalId,
                    currency_id: $cashAdvance->currency_id,
                    entry_date: Carbon::now(),
                    reference: "Reimbursement {$cashAdvance->advance_number}",
                    description: "Reimbursement for cash advance shortfall {$cashAdvance->advance_number}",
                    created_by_user_id: $user->id,
                    is_posted: true,
                    lines: $lines,
                    source_type: CashAdvance::class,
                    source_id: $cashAdvance->id,
                );

                $reimburseJE = $this->createJournalEntryAction->execute($dto);
            }
            // 'payroll_deduction' or 'none' (carrying balance) just updates status

            $cashAdvance->update([
                'status' => CashAdvanceStatus::Settled,
                'settled_at' => now(),
            ]);
        });
    }
}
