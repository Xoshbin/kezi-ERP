<?php

namespace App\Filament\Resources\AnalyticAccountResource\Pages;

use App\Filament\Resources\AnalyticAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAnalyticAccount extends CreateRecord
{
    protected static string $resource = AnalyticAccountResource::class;

    public function getTitle(): string
    {
        return __('analytic_account.pages.create.title');
    }
}
