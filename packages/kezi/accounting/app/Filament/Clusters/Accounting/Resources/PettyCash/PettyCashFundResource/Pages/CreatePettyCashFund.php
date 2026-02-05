<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashFundResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashFundResource;
use Kezi\Payment\Actions\PettyCash\CreatePettyCashFundAction;
use Kezi\Payment\DataTransferObjects\PettyCash\CreatePettyCashFundDTO;

/**
 * @extends CreateRecord<\Kezi\Payment\Models\PettyCash\PettyCashFund>
 */
class CreatePettyCashFund extends CreateRecord
{
    protected static string $resource = PettyCashFundResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = filament()->getTenant()->id;
        $data['status'] = 'active';

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $dto = new CreatePettyCashFundDTO(
            company_id: $data['company_id'],
            name: $data['name'],
            custodian_id: $data['custodian_id'],
            account_id: $data['account_id'],
            bank_account_id: $data['bank_account_id'],
            currency_id: $data['currency_id'],
            imprest_amount: \Brick\Money\Money::of($data['imprest_amount'], 'IQD'),
        );

        return app(CreatePettyCashFundAction::class)->execute($dto, auth()->user());
    }
}
