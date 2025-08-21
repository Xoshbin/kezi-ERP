<?php

namespace App\Actions\Accounting;

use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Payroll;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateJournalEntryForPayrollAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction)
    {
    }

    public function execute(Payroll $payroll, User $user): JournalEntry
    {
        return DB::transaction(function () use ($payroll, $user) {
            $payroll->load('company', 'currency', 'employee', 'payrollLines.account');

            $company = $payroll->company;
            $currency = $payroll->currency;

            // Get required default accounts from company
            $salaryPayableAccountId = $company->default_salary_payable_account_id ?? null;
            $payrollJournalId = $company->default_payroll_journal_id ?? $company->default_purchase_journal_id;

            if (!$salaryPayableAccountId) {
                throw new RuntimeException('Default Salary Payable account is not configured for this company.');
            }

            if (!$payrollJournalId) {
                throw new RuntimeException('Default Payroll Journal is not configured for this company.');
            }

            $lineDTOs = [];
            $totalDebit = Money::of(0, $currency->code);

            // Process payroll lines to create journal entry lines
            foreach ($payroll->payrollLines as $payrollLine) {
                $amount = $payrollLine->amount;

                if ($payrollLine->debit_credit === 'debit') {
                    // Debit expense accounts (salary, benefits, etc.)
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $payrollLine->account_id,
                        debit: $amount,
                        credit: Money::of(0, $currency->code),
                        description: $payrollLine->description['en'] ?? $payrollLine->code,
                        partner_id: null, // Payroll is not partner-specific
                        analytic_account_id: $payrollLine->analytic_account_id,
                    );
                    $totalDebit = $totalDebit->plus($amount);
                } else {
                    // Credit liability accounts (taxes, deductions)
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $payrollLine->account_id,
                        debit: Money::of(0, $currency->code),
                        credit: $amount,
                        description: $payrollLine->description['en'] ?? $payrollLine->code,
                        partner_id: null,
                        analytic_account_id: $payrollLine->analytic_account_id,
                    );
                }
            }

            // Credit Salary Payable for the net salary amount
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $salaryPayableAccountId,
                debit: Money::of(0, $currency->code),
                credit: $payroll->net_salary,
                description: 'Salary Payable - ' . $payroll->employee->full_name,
                partner_id: null,
                analytic_account_id: null,
            );

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $payrollJournalId,
                currency_id: $currency->id,
                entry_date: $payroll->pay_date,
                reference: $payroll->payroll_number,
                description: 'Payroll for ' . $payroll->employee->full_name . ' - ' . $payroll->period_start_date . ' to ' . $payroll->period_end_date,
                source_type: Payroll::class,
                source_id: $payroll->id,
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lineDTOs,
            );

            return $this->createJournalEntryAction->execute($journalEntryDTO);
        });
    }
}
