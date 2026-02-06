<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\ExpenseReportResource;

class ListExpenseReports extends ListRecords
{
    protected static string $resource = ExpenseReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('expense-reports'),
        ];
    }
}
