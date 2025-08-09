<?php

namespace App\Filament\Resources\AnalyticPlanResource\Pages;

use App\Filament\Resources\AnalyticPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAnalyticPlan extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = AnalyticPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('analytic_plan.pages.create');
    }
}
