<?php

namespace Kezi\HR\Actions\HumanResources;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\HR\Enums\ExpenseReportStatus;
use Kezi\HR\Models\ExpenseReport;

class ApproveExpenseReportAction
{
    public function execute(ExpenseReport $expenseReport, User $user): void
    {
        DB::transaction(function () use ($expenseReport, $user) {
            if ($expenseReport->status !== ExpenseReportStatus::Submitted) {
                throw new \InvalidArgumentException(__('hr::exceptions.expense_report.only_submitted_can_be_approved'));
            }

            $expenseReport->update([
                'status' => ExpenseReportStatus::Approved,
                'approved_at' => now(),
                'approved_by_user_id' => $user->id,
            ]);
        });
    }
}
