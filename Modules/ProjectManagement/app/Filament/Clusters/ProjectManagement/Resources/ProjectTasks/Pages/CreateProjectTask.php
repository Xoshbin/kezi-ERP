<?php

namespace Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\ProjectTaskResource;

class CreateProjectTask extends CreateRecord
{
    protected static string $resource = ProjectTaskResource::class;
}
