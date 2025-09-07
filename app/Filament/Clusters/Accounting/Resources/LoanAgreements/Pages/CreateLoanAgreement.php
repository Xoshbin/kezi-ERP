<?php

namespace App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages;

use App\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanAgreement extends CreateRecord
{
    protected static string $resource = LoanAgreementResource::class;
}
