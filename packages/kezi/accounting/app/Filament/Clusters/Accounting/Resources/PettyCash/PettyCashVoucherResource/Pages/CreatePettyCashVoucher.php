<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource;
use Kezi\Payment\Actions\PettyCash\CreatePettyCashVoucherAction;
use Kezi\Payment\DataTransferObjects\PettyCash\CreatePettyCashVoucherDTO;

/**
 * @extends CreateRecord<\Kezi\Payment\Models\PettyCash\PettyCashVoucher>
 */
class CreatePettyCashVoucher extends CreateRecord
{
    protected static string $resource = PettyCashVoucherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = filament()->getTenant()->id;

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $dto = new CreatePettyCashVoucherDTO(
            company_id: $data['company_id'],
            fund_id: $data['fund_id'],
            expense_account_id: $data['expense_account_id'],
            amount: \Brick\Money\Money::of($data['amount'], 'IQD'),
            voucher_date: $data['voucher_date'],
            description: $data['description'],
            partner_id: $data['partner_id'] ?? null,
            receipt_reference: $data['receipt_reference'] ?? null,
        );

        return app(CreatePettyCashVoucherAction::class)->execute($dto, auth()->user());
    }
}
