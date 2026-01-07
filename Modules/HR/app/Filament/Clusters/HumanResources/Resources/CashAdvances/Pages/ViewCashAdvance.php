<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\CashAdvanceResource;

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
