<?php

namespace Jmeryar\HR\Actions\HumanResources;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Jmeryar\HR\Enums\ExpenseReportStatus;
use Jmeryar\HR\Models\ExpenseReport;

class ApproveExpenseReportAction
{
    public function execute(ExpenseReport $expenseReport, User $user): void
    {
        DB::transaction(function () use ($expenseReport, $user) {
            if ($expenseReport->status !== ExpenseReportStatus::Submitted) {
                throw new \InvalidArgumentException('Only submitted expense reports can be approved.');
            }

            $expenseReport->update([
                'status' => ExpenseReportStatus::Approved,
                'approved_at' => now(),
                'approved_by_user_id' => $user->id,
            ]);
        });
    }
}
