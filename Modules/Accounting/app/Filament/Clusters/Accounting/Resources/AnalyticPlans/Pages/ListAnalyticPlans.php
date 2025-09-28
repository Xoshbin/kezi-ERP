<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\AnalyticPlanResource;

class ListAnalyticPlans extends ListRecords
{
    use Translatable;

    protected static string $resource = AnalyticPlanResource::class;

    public function getTitle(): string
    {
        return __('analytic_plan.pages.list');
    }

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
