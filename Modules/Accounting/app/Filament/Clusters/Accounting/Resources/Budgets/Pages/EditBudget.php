<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Budgets\Pages;

use App\Filament\Clusters\Accounting\Resources\Budgets\BudgetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBudget extends EditRecord
{
    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
