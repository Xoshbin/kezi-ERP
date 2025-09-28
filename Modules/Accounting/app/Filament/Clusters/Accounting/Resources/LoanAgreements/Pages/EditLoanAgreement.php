<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;

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
