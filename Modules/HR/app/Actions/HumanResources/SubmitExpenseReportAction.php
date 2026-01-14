<?php

namespace Modules\HR\Actions\HumanResources;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\HR\Enums\CashAdvanceStatus;
use Modules\HR\Enums\ExpenseReportStatus;
use Modules\HR\Models\ExpenseReport;

class SubmitExpenseReportAction
{
    public function execute(ExpenseReport $expenseReport, User $user): void
    {
        DB::transaction(function () use ($expenseReport) {
            if ($expenseReport->status !== ExpenseReportStatus::Draft) {
                throw new \InvalidArgumentException('Only draft expense reports can be submitted.');
            }

            $expenseReport->update([
                'status' => ExpenseReportStatus::Submitted,
                'submitted_at' => now(),
            ]);

            // Update parent cash advance status if not already pending settlement
            $cashAdvance = $expenseReport->cashAdvance;
            if ($cashAdvance->status !== CashAdvanceStatus::PendingSettlement) {
                $cashAdvance->update([
                    'status' => CashAdvanceStatus::PendingSettlement,
                ]);
            }
        });
    }
}
