<?php

namespace App\Observers;

use App\Models\BankStatementLine;

class BankStatementLineObserver
{
    /**
     * Handle the BankStatementLine "creating" event.
     *
     * This ensures that every line automatically inherits the company_id
     * from its parent BankStatement, maintaining data integrity and tenancy.
     */
    public function creating(BankStatementLine $line): void
    {
        if ($line->bankStatement) {
            // This line is correct and necessary.
            $line->company_id = $line->bankStatement->company_id;
        }
    }
}
