<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\AnalyticAccountResource;

/**
 * @extends CreateRecord<\Kezi\Accounting\Models\AnalyticAccount>
 */
class CreateAnalyticAccount extends CreateRecord
{
    protected static string $resource = AnalyticAccountResource::class;

    public function getTitle(): string
    {
        return __('accounting::analytic_account.pages.create.title');
    }
}
