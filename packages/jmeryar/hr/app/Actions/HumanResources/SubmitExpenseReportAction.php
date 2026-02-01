<?php

namespace Jmeryar\HR\Actions\HumanResources;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Jmeryar\HR\Enums\CashAdvanceStatus;
use Jmeryar\HR\Enums\ExpenseReportStatus;
use Jmeryar\HR\Models\ExpenseReport;

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
