<?php

namespace Kezi\ProjectManagement\Actions;

use Illuminate\Support\Facades\DB;
use Kezi\ProjectManagement\DataTransferObjects\CreateProjectInvoiceDTO;
use Kezi\ProjectManagement\Models\Project;
use Kezi\ProjectManagement\Models\ProjectInvoice;

class CreateProjectInvoiceAction
{
    public function execute(CreateProjectInvoiceDTO $dto): ProjectInvoice
    {
        return DB::transaction(function () use ($dto) {
            $project = Project::findOrFail($dto->project_id);

            // Calculate Labor Amount
            $laborAmount = \Brick\Money\Money::zero($project->company->currency->code);
            if ($dto->include_labor) {
                // Get approved billable timesheet lines in period
                $hours = $project->timesheetLines()
                    ->where('is_billable', true)
                    ->whereBetween('date', [$dto->period_start, $dto->period_end])
                    ->whereHas('timesheet', function ($query) {
                        $query->where('status', 'approved');
                    })
                    ->sum('hours');

                // dumps($hours);

                $hourlyRate = $project->hourly_rate ?? 0;
                $laborAmount = \Brick\Money\Money::of($hours * $hourlyRate, $project->company->currency->code);
            }

            // Calculate Expense Amount
            $expenseAmount = \Brick\Money\Money::zero($project->company->currency->code);
            if ($dto->include_expenses) {
                $expenseSum = \Kezi\Accounting\Models\JournalEntryLine::query()
                    ->where('analytic_account_id', $project->analytic_account_id)
                    ->whereHas('journalEntry', function ($query) use ($dto) {
                        $query->whereBetween('entry_date', [$dto->period_start, $dto->period_end]);
                    })
                    ->sum(DB::raw('debit - credit'));

                $expenseAmount = \Brick\Money\Money::ofMinor($expenseSum, $project->company->currency->code);
            }

            $totalAmount = $laborAmount->plus($expenseAmount);

            $projectInvoice = new ProjectInvoice;
            $projectInvoice->company_id = $dto->company_id;
            $projectInvoice->project_id = $dto->project_id;
            $projectInvoice->invoice_date = now();
            $projectInvoice->period_start = $dto->period_start;
            $projectInvoice->period_end = $dto->period_end;
            $projectInvoice->labor_amount = $laborAmount;
            $projectInvoice->expense_amount = $expenseAmount;
            $projectInvoice->total_amount = $totalAmount;
            $projectInvoice->status = 'draft';
            $projectInvoice->save();

            return $projectInvoice;
        });
    }
}
