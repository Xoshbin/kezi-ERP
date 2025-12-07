<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\AnalyticAccountResource;

class CreateAnalyticAccount extends CreateRecord
{
    protected static string $resource = AnalyticAccountResource::class;

    public function getTitle(): string
    {
        return __('accounting::analytic_account.pages.create.title');
    }
}
