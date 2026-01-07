<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages;

use Filament\Resources\Pages\CreateRecord;

class CreateExpenseReport extends CreateRecord
{
    protected static string $resource = \Modules\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\ExpenseReportResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $cashAdvance = \Modules\HR\Models\CashAdvance::findOrFail($data['cash_advance_id']);
        $currencyCode = $cashAdvance->currency->code;

        $linesData = $data['lines'] ?? $this->data['lines'] ?? [];
        $lines = collect($linesData)->map(function ($line) use ($currencyCode) {
            return new \Modules\HR\DataTransferObjects\HumanResources\ExpenseReportLineDTO(
                expense_account_id: $line['expense_account_id'],
                description: $line['description'],
                expense_date: $line['expense_date'],
                amount: \Brick\Money\Money::of($line['amount'], $currencyCode),
                receipt_reference: $line['receipt_reference'] ?? null,
                partner_id: $line['partner_id'] ?? null, // Can be null
            );
        })->toArray();

        $dto = new \Modules\HR\DataTransferObjects\HumanResources\CreateExpenseReportDTO(
            company_id: \Filament\Facades\Filament::getTenant()->id,
            cash_advance_id: $data['cash_advance_id'],
            employee_id: $cashAdvance->employee_id, // Get employee from cash advance
            report_date: $data['report_date'],
            lines: $lines,
            notes: $data['notes'] ?? null,
        );

        return app(\Modules\HR\Actions\HumanResources\CreateExpenseReportActionV3::class)->execute($dto, auth()->user());
    }
}
