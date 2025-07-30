<?php

namespace App\Filament\Resources\AnalyticPlanResource\Pages;

use App\Filament\Resources\AnalyticPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnalyticPlans extends ListRecords
{
    protected static string $resource = AnalyticPlanResource::class;

    public function getTitle(): string
    {
        return __('analytic_plan.pages.list');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
