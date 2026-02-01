<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\ExpenseReportResource;

class ViewExpenseReport extends ViewRecord
{
    protected static string $resource = ExpenseReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
