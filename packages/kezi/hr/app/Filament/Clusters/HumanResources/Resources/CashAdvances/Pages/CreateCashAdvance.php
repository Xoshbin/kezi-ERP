<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\CashAdvanceResource;

/**
 * @extends CreateRecord<\Kezi\HR\Models\CashAdvance>
 */
class CreateCashAdvance extends CreateRecord
{
    protected static string $resource = CashAdvanceResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $dto = new \Kezi\HR\DataTransferObjects\HumanResources\CreateCashAdvanceDTO(
            company_id: \Filament\Facades\Filament::getTenant()->id,
            employee_id: $data['employee_id'],
            currency_id: $data['currency_id'],
            requested_amount: \Brick\Money\Money::of($data['requested_amount'], \Kezi\Foundation\Models\Currency::find($data['currency_id'])->code),
            purpose: $data['purpose'],
            expected_return_date: $data['expected_return_date'] ?? null,
            notes: $data['notes'] ?? null,
        );

        return app(\Kezi\HR\Actions\HumanResources\CreateCashAdvanceAction::class)->execute($dto, auth()->user());
    }
}
