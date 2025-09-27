<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages;

use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanAgreement extends CreateRecord
{
    protected static string $resource = LoanAgreementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant?->getKey() ?? 0;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('loan-agreements'),
        ];
    }
}
