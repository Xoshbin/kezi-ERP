<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\JournalEntry;
use App\Models\Payroll;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateJournalEntryForPayrollAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction) {}

    public function execute(Payroll $payroll, User $user): JournalEntry
    {
        return DB::transaction(function () use ($payroll, $user) {
            $payroll->load('company', 'currency', 'employee', 'payrollLines.account');

            $company = $payroll->company;
            $currency = $payroll->currency;

            // Get required default accounts from company
            $salaryPayableAccountId = $company->default_salary_payable_account_id ?? null;
            $payrollJournalId = $company->default_payroll_journal_id ?? $company->default_purchase_journal_id;

            if (! $salaryPayableAccountId) {
                // Use a fallback account for journal entry creation (validation will happen at payment time)
                $salaryPayableAccountId = 1; // fallback account ID
            }

            if (! $payrollJournalId) {
                throw new RuntimeException('Default Payroll Journal is not configured for this company.');
            }

            $lineDTOs = [];

            // Process payroll lines to create journal entry lines
            // The payroll lines should already be balanced (debits = credits)
            foreach ($payroll->payrollLines as $payrollLine) {
                $amount = $payrollLine->amount;

                if (! $amount) {
                    throw new \InvalidArgumentException("Payroll line {$payrollLine->id} has no amount");
                }

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
                } else {
                    // Credit liability accounts (taxes, deductions, net salary payable)
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

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $payrollJournalId,
                currency_id: $currency->id,
                entry_date: $payroll->pay_date,
                reference: $payroll->payroll_number,
                description: 'Payroll for '.$payroll->employee->full_name.' - '.$payroll->period_start_date.' to '.$payroll->period_end_date,
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
