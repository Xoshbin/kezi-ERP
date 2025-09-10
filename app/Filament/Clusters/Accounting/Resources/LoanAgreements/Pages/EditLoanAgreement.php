<?php

namespace App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages;

use App\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use App\Filament\Actions\DocsAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLoanAgreement extends EditRecord
{
    protected static string $resource = LoanAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('loan-agreements'),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
