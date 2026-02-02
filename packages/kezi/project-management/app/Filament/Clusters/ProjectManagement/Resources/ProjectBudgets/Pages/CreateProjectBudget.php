<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\ProjectBudgetResource;

class CreateProjectBudget extends CreateRecord
{
    protected static string $resource = ProjectBudgetResource::class;
}
