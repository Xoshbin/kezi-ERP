<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use Modules\Foundation\Filament\Actions\DocsAction;

class ListLoanAgreements extends ListRecords
{
    protected static string $resource = LoanAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('loan-agreements'),
        ];
    }
}
