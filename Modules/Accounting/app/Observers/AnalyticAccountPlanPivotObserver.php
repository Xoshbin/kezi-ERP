<?php

namespace Modules\Accounting\Observers;

use Filament\Facades\Filament;
use Modules\Accounting\Models\AnalyticAccountPlanPivot;

class AnalyticAccountPlanPivotObserver
{
    /**
     * Handle the AnalyticAccountPlanPivot "creating" event.
     */
    public function creating(AnalyticAccountPlanPivot $pivot): void
    {
        if (empty($pivot->company_id)) {
            $pivot->company_id = Filament::getTenant()?->id;
        }
    }
}
