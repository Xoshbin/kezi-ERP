<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\AnalyticPlanResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

/**
 * @extends CreateRecord<\Kezi\Accounting\Models\AnalyticPlan>
 */
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
        return __('accounting::analytic_plan.pages.create');
    }
}
