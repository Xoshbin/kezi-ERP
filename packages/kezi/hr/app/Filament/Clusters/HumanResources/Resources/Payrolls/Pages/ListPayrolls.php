<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\PayrollResource;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('payroll-processing'),
            CreateAction::make(),
        ];
    }
}
