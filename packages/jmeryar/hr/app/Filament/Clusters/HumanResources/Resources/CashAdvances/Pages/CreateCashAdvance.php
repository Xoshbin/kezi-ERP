<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages;

use Filament\Resources\Pages\CreateRecord;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\CashAdvanceResource;

class CreateCashAdvance extends CreateRecord
{
    protected static string $resource = CashAdvanceResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $dto = new \Jmeryar\HR\DataTransferObjects\HumanResources\CreateCashAdvanceDTO(
            company_id: \Filament\Facades\Filament::getTenant()->id,
            employee_id: $data['employee_id'],
            currency_id: $data['currency_id'],
            requested_amount: \Brick\Money\Money::of($data['requested_amount'], \Jmeryar\Foundation\Models\Currency::find($data['currency_id'])->code),
            purpose: $data['purpose'],
            expected_return_date: $data['expected_return_date'] ?? null,
            notes: $data['notes'] ?? null,
        );

        return app(\Jmeryar\HR\Actions\HumanResources\CreateCashAdvanceAction::class)->execute($dto, auth()->user());
    }
}
