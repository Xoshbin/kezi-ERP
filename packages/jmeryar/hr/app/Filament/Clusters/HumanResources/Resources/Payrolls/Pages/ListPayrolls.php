<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Payrolls\PayrollResource;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('payroll-processing'),
            CreateAction::make(),
        ];
    }
}
