<?php

namespace App\Filament\Resources\AnalyticPlanResource\Pages;

use App\Filament\Resources\AnalyticPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnalyticPlan extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = AnalyticPlanResource::class;

    public function getTitle(): string
    {
        return __('analytic_plan.pages.edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
