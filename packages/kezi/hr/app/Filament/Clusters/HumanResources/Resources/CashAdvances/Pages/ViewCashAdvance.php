<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages;

use \Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\CashAdvanceResource;

/**
 * @extends ViewRecord<\Kezi\HR\Models\CashAdvance>
 */
class ViewCashAdvance extends ViewRecord
{
    protected static string $resource = CashAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
