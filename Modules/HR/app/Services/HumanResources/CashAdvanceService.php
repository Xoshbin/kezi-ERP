<?php

namespace Modules\HR\Services\HumanResources;

use App\Models\User;
use Brick\Money\Money;
use Modules\HR\Actions\HumanResources\ApproveCashAdvanceAction;
use Modules\HR\Actions\HumanResources\ApproveExpenseReportAction;
use Modules\HR\Actions\HumanResources\CreateCashAdvanceAction;
use Modules\HR\Actions\HumanResources\CreateExpenseReportActionV3;
use Modules\HR\Actions\HumanResources\DisburseCashAdvanceAction;
use Modules\HR\Actions\HumanResources\RejectCashAdvanceAction;
use Modules\HR\Actions\HumanResources\SettleCashAdvanceAction;
use Modules\HR\Actions\HumanResources\SubmitCashAdvanceAction;
use Modules\HR\Actions\HumanResources\SubmitExpenseReportAction;
use Modules\HR\DataTransferObjects\HumanResources\CreateCashAdvanceDTO;
use Modules\HR\DataTransferObjects\HumanResources\CreateExpenseReportDTO;
use Modules\HR\Models\CashAdvance;
use Modules\HR\Models\ExpenseReport;

class CashAdvanceService
{
    public function __construct(
        protected CreateCashAdvanceAction $createAction,
        protected SubmitCashAdvanceAction $submitAction,
        protected ApproveCashAdvanceAction $approveAction,
        protected RejectCashAdvanceAction $rejectAction,
        protected DisburseCashAdvanceAction $disburseAction,
        protected CreateExpenseReportActionV3 $createExpenseReportAction,
        protected SubmitExpenseReportAction $submitExpenseReportAction,
        protected ApproveExpenseReportAction $approveExpenseReportAction,
        protected SettleCashAdvanceAction $settleAction,
    ) {}

    public function createAdvance(CreateCashAdvanceDTO $dto, User $user): CashAdvance
    {
        return $this->createAction->execute($dto, $user);
    }

    public function submitForApproval(CashAdvance $cashAdvance, User $user): void
    {
        $this->submitAction->execute($cashAdvance, $user);
    }

    public function approve(CashAdvance $cashAdvance, Money $approvedAmount, User $user): void
    {
        $this->approveAction->execute($cashAdvance, $approvedAmount, $user);
    }

    public function reject(CashAdvance $cashAdvance, string $reason, User $user): void
    {
        $this->rejectAction->execute($cashAdvance, $reason, $user);
    }

    public function disburse(CashAdvance $cashAdvance, int $bankAccountId, User $user): void
    {
        $this->disburseAction->execute($cashAdvance, $bankAccountId, $user);
    }

    public function createExpenseReport(CreateExpenseReportDTO $dto, User $user): ExpenseReport
    {
        return $this->createExpenseReportAction->execute($dto, $user);
    }

    public function submitExpenseReport(ExpenseReport $expenseReport, User $user): void
    {
        $this->submitExpenseReportAction->execute($expenseReport, $user);
    }

    public function approveExpenseReport(ExpenseReport $expenseReport, User $user): void
    {
        $this->approveExpenseReportAction->execute($expenseReport, $user);
    }

    public function settle(CashAdvance $cashAdvance, string $settlementMethod, ?int $bankAccountId, User $user): void
    {
        $this->settleAction->execute($cashAdvance, $settlementMethod, $bankAccountId, $user);
    }
}
