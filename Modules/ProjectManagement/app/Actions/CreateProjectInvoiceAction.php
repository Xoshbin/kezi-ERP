<?php

namespace Modules\ProjectManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\ProjectManagement\DataTransferObjects\CreateProjectInvoiceDTO;
use Modules\ProjectManagement\Models\Project;
use Modules\ProjectManagement\Models\ProjectInvoice;

class CreateProjectInvoiceAction
{
    public function execute(CreateProjectInvoiceDTO $dto): ProjectInvoice
    {
        return DB::transaction(function () use ($dto) {
            $project = Project::findOrFail($dto->project_id);

            // Calculate Labor Amount
            $laborAmount = 0;
            if ($dto->include_labor) {
                // Get approved billable timesheet lines in period
                $hours = $project->timesheetLines()
                    ->where('is_billable', true)
                    ->whereBetween('date', [$dto->period_start, $dto->period_end])
                    ->whereHas('timesheet', function ($query) {
                        $query->where('status', 'approved');
                    })
                    ->sum('hours');

                // Rate calculation placeholder - assuming 0 or fetched from config/contract
                // In real implementation, we'd fetch rate from Employee contract or Project billing rate
                $hourlyRate = 0;
                $laborAmount = $hours * $hourlyRate;
            }

            // Calculate Expense Amount
            $expenseAmount = 0;
            if ($dto->include_expenses) {
                // Should fetch from Journal Entries linked to Analytic Account
                // Placeholder
            }

            $totalAmount = $laborAmount + $expenseAmount;

            return ProjectInvoice::create([
                'company_id' => $dto->company_id,
                'project_id' => $dto->project_id,
                'invoice_date' => now(), // Default to today
                'period_start' => $dto->period_start,
                'period_end' => $dto->period_end,
                'labor_amount' => $laborAmount,
                'expense_amount' => $expenseAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
            ]);
        });
    }
}
