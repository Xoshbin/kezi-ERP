<?php

namespace Kezi\HR\Actions\HumanResources;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\Foundation\Services\SequenceService;
use Kezi\HR\DataTransferObjects\HumanResources\CreateCashAdvanceDTO;
use Kezi\HR\Enums\CashAdvanceStatus;
use Kezi\HR\Models\CashAdvance;

class CreateCashAdvanceAction
{
    public function __construct(
        private readonly SequenceService $sequenceService,
    ) {}

    public function execute(CreateCashAdvanceDTO $dto, User $user): CashAdvance
    {
        return DB::transaction(function () use ($dto) {
            $company = \App\Models\Company::findOrFail($dto->company_id);

            $advanceNumber = $this->sequenceService->getNextNumber(
                $company,
                'cash_advance',
                'CA'
            );

            return CashAdvance::create([
                'company_id' => $dto->company_id,
                'employee_id' => $dto->employee_id,
                'currency_id' => $dto->currency_id,
                'advance_number' => $advanceNumber,
                'requested_amount' => $dto->requested_amount,
                'purpose' => $dto->purpose,
                'expected_return_date' => $dto->expected_return_date,
                'status' => CashAdvanceStatus::Draft,
                'notes' => $dto->notes,
            ]);
        });
    }
}
