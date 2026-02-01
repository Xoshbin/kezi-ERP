<?php

namespace Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\ProjectBudgetResource;

class CreateProjectBudget extends CreateRecord
{
    protected static string $resource = ProjectBudgetResource::class;
}
