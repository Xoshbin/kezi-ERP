<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Budgets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Budgets\BudgetResource;

/**
 * @extends CreateRecord<\Kezi\Accounting\Models\Budget>
 */
class CreateBudget extends CreateRecord
{
    protected static string $resource = BudgetResource::class;
}
