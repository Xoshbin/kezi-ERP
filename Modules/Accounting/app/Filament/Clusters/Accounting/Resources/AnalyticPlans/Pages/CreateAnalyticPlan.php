<?php

namespace App\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages;

use App\Filament\Clusters\Accounting\Resources\AnalyticPlans\AnalyticPlanResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

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
