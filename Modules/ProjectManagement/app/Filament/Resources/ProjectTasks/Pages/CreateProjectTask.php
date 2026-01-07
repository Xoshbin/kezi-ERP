<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectTasks\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\ProjectManagement\Filament\Resources\ProjectTasks\ProjectTaskResource;

class CreateProjectTask extends CreateRecord
{
    protected static string $resource = ProjectTaskResource::class;
}
