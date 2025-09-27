<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Pages;

use App\Filament\Clusters\HumanResources\Resources\Payrolls\PayrollResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
