<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use Kezi\Foundation\Filament\Actions\DocsAction;

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
