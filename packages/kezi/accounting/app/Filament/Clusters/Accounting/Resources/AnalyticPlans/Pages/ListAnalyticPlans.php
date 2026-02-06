<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\AnalyticPlanResource;
use Kezi\Foundation\Filament\Actions\DocsAction;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListAnalyticPlans extends ListRecords
{
    use Translatable;

    protected static string $resource = AnalyticPlanResource::class;

    public function getTitle(): string
    {
        return __('accounting::analytic_plan.pages.list');
    }

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make(),
            DocsAction::make('analytic-configuration'),
        ];
    }
}
