<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\AnalyticPlanResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditAnalyticPlan extends EditRecord
{
    use Translatable;

    protected static string $resource = AnalyticPlanResource::class;

    public function getTitle(): string
    {
        return __('accounting::analytic_plan.pages.edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
