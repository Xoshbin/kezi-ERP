<?php

namespace App\Filament\Resources\AnalyticPlanResource\Pages;

use App\Filament\Resources\AnalyticPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAnalyticPlan extends CreateRecord
{
    protected static string $resource = AnalyticPlanResource::class;

    public function getTitle(): string
    {
        return __('analytic_plan.pages.create');
    }
}
