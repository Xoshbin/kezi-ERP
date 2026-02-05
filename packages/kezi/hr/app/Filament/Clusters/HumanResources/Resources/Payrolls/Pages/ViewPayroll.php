<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Pages;

use \Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\PayrollResource;

/**
 * @extends ViewRecord<\Kezi\HR\Models\Payroll>
 */
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
