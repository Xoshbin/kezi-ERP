<?php

namespace Kezi\HR\Actions\HumanResources;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Kezi\Foundation\Services\SequenceService;
use Kezi\HR\DataTransferObjects\HumanResources\CreateExpenseReportDTO;
use Kezi\HR\Enums\ExpenseReportStatus;
use Kezi\HR\Models\CashAdvance;
use Kezi\HR\Models\ExpenseReport;

class CreateExpenseReportActionV3
{
    public function __construct(
        private readonly SequenceService $sequenceService,
    ) {}

    public function execute(CreateExpenseReportDTO $dto, User $user): ExpenseReport
    {
        return DB::transaction(function () use ($dto) {
            $company = \App\Models\Company::findOrFail($dto->company_id);
            $cashAdvance = CashAdvance::findOrFail($dto->cash_advance_id);

            $reportNumber = $this->sequenceService->getNextNumber(
                $company,
                'expense_report',
                'ER'
            );

            // Calculate total amount from lines
            $totalAmount = Money::zero($cashAdvance->currency->code);
            foreach ($dto->lines as $lineDTO) {
                // Ensure line amount currency matches advance currency
                if ($lineDTO->amount->getCurrency()->getCurrencyCode() !== $cashAdvance->currency->code) {
                    throw new \InvalidArgumentException("Expense line currency {$lineDTO->amount->getCurrency()->getCurrencyCode()} does not match cash advance currency {$cashAdvance->currency->code}.");
                }
                $totalAmount = $totalAmount->plus($lineDTO->amount);
            }

            $expenseReport = ExpenseReport::create([
                'company_id' => $dto->company_id,
                'cash_advance_id' => $dto->cash_advance_id,
                'employee_id' => $dto->employee_id,
                'report_number' => $reportNumber,
                'report_date' => $dto->report_date,
                'total_amount' => $totalAmount,
                'status' => ExpenseReportStatus::Draft,
                'notes' => $dto->notes,
            ]);

            foreach ($dto->lines as $lineDTO) {
                DB::insert('insert into expense_report_lines (company_id, expense_report_id, expense_account_id, partner_id, description, expense_date, amount, receipt_reference, created_at, updated_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                    $dto->company_id,
                    $expenseReport->id,
                    $lineDTO->expense_account_id,
                    $lineDTO->partner_id,
                    $lineDTO->description,
                    $lineDTO->expense_date,
                    $lineDTO->amount->getMinorAmount()->toInt(),
                    $lineDTO->receipt_reference,
                    now(),
                    now(),
                ]);
            }

            return $expenseReport;
        });
    }
}
