<?php

namespace Kezi\HR\Observers;

use App\Models\Company;
use Filament\Facades\Filament;
use Kezi\HR\Models\EmploymentContract;

class EmploymentContractObserver
{
    /**
     * Handle the EmploymentContract "creating" event.
     */
    public function creating(EmploymentContract $contract): void
    {
        /** @var Company|null $tenant */
        $tenant = Filament::getTenant();

        if (empty($contract->company_id) && $tenant) {
            $contract->company_id = $tenant->id;
        }

        if (empty($contract->contract_number) && $tenant) {
            $contract->contract_number = EmploymentContract::generateContractNumber($tenant);
        }
    }
}
