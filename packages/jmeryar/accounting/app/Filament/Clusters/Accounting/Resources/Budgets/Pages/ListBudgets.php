<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Budgets\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Budgets\BudgetResource;
use Jmeryar\Foundation\Filament\Actions\DocsAction;

class ListBudgets extends ListRecords
{
    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('budget-management'),
            CreateAction::make(),
        ];
    }
}
