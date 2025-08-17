<?php

namespace App\Filament\Resources\AnalyticPlans\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use App\Filament\Resources\AnalyticPlans\AnalyticPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAnalyticPlan extends CreateRecord
{
    use Translatable;

    protected static string $resource = AnalyticPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('analytic_plan.pages.create');
    }
}
