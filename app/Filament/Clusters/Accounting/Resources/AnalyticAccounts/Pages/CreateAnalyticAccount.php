<?php

namespace App\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages;

use App\Filament\Clusters\Accounting\Resources\AnalyticAccounts\AnalyticAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnalyticAccount extends CreateRecord
{
    protected static string $resource = AnalyticAccountResource::class;

    public function getTitle(): string
    {
        return __('analytic_account.pages.create.title');
    }
}
