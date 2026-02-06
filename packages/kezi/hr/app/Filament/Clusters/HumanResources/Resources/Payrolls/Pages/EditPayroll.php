<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\PayrollResource;

/**
 * @extends EditRecord<\Kezi\HR\Models\Payroll>
 */
class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
