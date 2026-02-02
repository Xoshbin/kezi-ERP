<?php

namespace Kezi\Accounting\Actions\Accounting;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateFiscalYearDTO;
use Kezi\Accounting\Enums\Accounting\FiscalYearState;
use Kezi\Accounting\Models\FiscalPeriod;
use Kezi\Accounting\Models\FiscalYear;

class CreateFiscalYearAction
{
    /**
     * Execute the action to create a new fiscal year.
     *
     * @throws ValidationException
     */
    public function execute(CreateFiscalYearDTO $dto): FiscalYear
    {
        return DB::transaction(function () use ($dto) {
            // Validate no overlapping fiscal years for this company
            $this->validateNoOverlap($dto);

            // Create the fiscal year
            $fiscalYear = FiscalYear::create([
                'company_id' => $dto->companyId,
                'name' => $dto->name,
                'start_date' => $dto->startDate,
                'end_date' => $dto->endDate,
                'state' => FiscalYearState::Open,
            ]);

            // Optionally generate monthly periods
            if ($dto->generatePeriods) {
                $this->generateMonthlyPeriods($fiscalYear);
            }

            return $fiscalYear;
        });
    }

    /**
     * Validate that the new fiscal year does not overlap with existing ones.
     *
     * @throws ValidationException
     */
    private function validateNoOverlap(CreateFiscalYearDTO $dto): void
    {
        $overlapping = FiscalYear::where('company_id', $dto->companyId)
            ->where(function ($query) use ($dto) {
                $query->whereBetween('start_date', [$dto->startDate, $dto->endDate])
                    ->orWhereBetween('end_date', [$dto->startDate, $dto->endDate])
                    ->orWhere(function ($q) use ($dto) {
                        $q->where('start_date', '<=', $dto->startDate)
                            ->where('end_date', '>=', $dto->endDate);
                    });
            })
            ->exists();

        if ($overlapping) {
            throw ValidationException::withMessages([
                'start_date' => __('accounting::validation.fiscal_year.overlapping'),
            ]);
        }
    }

    /**
     * Generate monthly periods for the fiscal year.
     */
    private function generateMonthlyPeriods(FiscalYear $fiscalYear): void
    {
        $current = $fiscalYear->start_date->copy();
        $end = $fiscalYear->end_date;

        while ($current->lte($end)) {
            $periodStart = $current->copy();
            $periodEnd = $current->copy()->endOfMonth();

            // If period end extends beyond fiscal year end, cap it
            if ($periodEnd->gt($end)) {
                $periodEnd = $end->copy();
            }

            FiscalPeriod::create([
                'fiscal_year_id' => $fiscalYear->id,
                'name' => $periodStart->format('F Y'),
                'start_date' => $periodStart,
                'end_date' => $periodEnd,
            ]);

            $current = $periodEnd->copy()->addDay();
        }
    }
}
