<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashReplenishmentResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashReplenishmentResource;
use Kezi\Payment\Actions\PettyCash\CreatePettyCashReplenishmentAction;
use Kezi\Payment\DataTransferObjects\PettyCash\CreatePettyCashReplenishmentDTO;

/**
 * @extends CreateRecord<\Kezi\Payment\Models\PettyCash\PettyCashReplenishment>
 */
class CreatePettyCashReplenishment extends CreateRecord
{
    protected static string $resource = PettyCashReplenishmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = filament()->getTenant()->id;

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $dto = new CreatePettyCashReplenishmentDTO(
            company_id: $data['company_id'],
            fund_id: $data['fund_id'],
            amount: \Brick\Money\Money::of($data['amount'], 'IQD'),
            replenishment_date: $data['replenishment_date'],
            payment_method: $data['payment_method'],
            reference: $data['reference'] ?? null,
        );

        return app(CreatePettyCashReplenishmentAction::class)->execute($dto, auth()->user());
    }
}
