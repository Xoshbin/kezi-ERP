<?php

namespace App\Exceptions\Reconciliation;

/**
 * Exception thrown when attempting to reconcile journal lines that are already reconciled.
 *
 * This exception is thrown when one or more journal entry lines are already
 * part of an existing reconciliation record.
 */
class AlreadyReconciledException extends ReconciliationException
{
    /**
     * @param array<int, int> $lineIds
     */
    public function __construct(array $lineIds = [], ?string $message = null)
    {
        $message = $message ?? sprintf(
            'The following journal entry lines are already reconciled: %s',
            implode(', ', $lineIds)
        );

        parent::__construct($message);
    }
}
