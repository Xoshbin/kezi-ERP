<?php

namespace App\Filament\Clusters\Accounting\Resources\Budgets\Pages;

use App\Filament\Clusters\Accounting\Resources\Budgets\BudgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBudget extends CreateRecord
{
    protected static string $resource = BudgetResource::class;
}
