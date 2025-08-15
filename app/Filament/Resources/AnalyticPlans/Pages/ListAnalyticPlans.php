<?php

namespace App\Filament\Resources\AnalyticPlans\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Filament\Actions\CreateAction;
use App\Filament\Resources\AnalyticPlans\AnalyticPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
