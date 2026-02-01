<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Budgets\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Budgets\BudgetResource;

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
