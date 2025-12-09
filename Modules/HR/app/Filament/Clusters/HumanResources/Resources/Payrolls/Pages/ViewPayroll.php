<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Payrolls\PayrollResource;

class ViewPayroll extends ViewRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
