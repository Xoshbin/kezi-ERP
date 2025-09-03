<?php

namespace App\Exceptions\Reconciliation;

/**
 * Exception thrown when attempting to reconcile journal lines from non-reconcilable accounts.
 *
 * This exception is thrown when one or more journal entry lines belong to accounts
 * that have allow_reconciliation set to false.
 */
class AccountNotReconcilableException extends ReconciliationException
{
    public function __construct(array $accountCodes = [], ?string $message = null)
    {
        $message = $message ?? sprintf(
            'The following accounts do not allow reconciliation: %s',
            implode(', ', $accountCodes)
        );

        parent::__construct($message);
    }
}
