<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\AnalyticPlanResource;

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
