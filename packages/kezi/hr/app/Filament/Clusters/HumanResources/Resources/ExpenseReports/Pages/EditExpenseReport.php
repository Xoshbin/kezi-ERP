<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\ExpenseReportResource;

class EditExpenseReport extends EditRecord
{
    protected static string $resource = ExpenseReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
