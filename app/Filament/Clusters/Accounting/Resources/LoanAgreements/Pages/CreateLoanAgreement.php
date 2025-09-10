<?php

namespace App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages;

use App\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use App\Filament\Actions\DocsAction;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanAgreement extends CreateRecord
{
    protected static string $resource = LoanAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('loan-agreements'),
        ];
    }
}
