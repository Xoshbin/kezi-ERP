<?php

namespace App\Observers;

use App\Models\BankStatement;
use App\Models\BankStatementLine;

class BankStatementLineObserver
{
    /**
     * Handle the BankStatementLine "creating" event.
     */
    public function creating(BankStatementLine $bankStatementLine): void
    {
        // If the 'bankStatement' relationship is not already loaded, but the
        // foreign key 'bank_statement_id' exists on the model instance,
        // we manually set the relationship. This is the crucial step that
        // provides the context needed by the MoneyCast just before the model is saved.
        if (! $bankStatementLine->relationLoaded('bankStatement') && $bankStatementLine->bank_statement_id) {
            $bankStatement = BankStatement::find($bankStatementLine->bank_statement_id);
            if ($bankStatement) {
                $bankStatementLine->setRelation('bankStatement', $bankStatement);
            }
        }
    }
}
