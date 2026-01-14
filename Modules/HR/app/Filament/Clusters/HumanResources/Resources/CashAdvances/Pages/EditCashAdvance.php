<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\CashAdvanceResource;

class EditCashAdvance extends EditRecord
{
    protected static string $resource = CashAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
