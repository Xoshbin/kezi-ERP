<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectBudgets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\ProjectManagement\Filament\Resources\ProjectBudgets\ProjectBudgetResource;

class CreateProjectBudget extends CreateRecord
{
    protected static string $resource = ProjectBudgetResource::class;
}
