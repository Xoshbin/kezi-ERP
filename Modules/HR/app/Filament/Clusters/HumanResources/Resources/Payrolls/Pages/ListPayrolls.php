<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Payrolls\PayrollResource;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('payroll-processing'),
            CreateAction::make(),
        ];
    }
}
