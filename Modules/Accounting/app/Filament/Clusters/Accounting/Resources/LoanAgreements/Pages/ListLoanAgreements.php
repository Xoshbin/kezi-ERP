<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages;

use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
