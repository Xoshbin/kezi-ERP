<?php

namespace Modules\Accounting\Exceptions\Reconciliation;

use Brick\Money\Money;

/**
 * Exception thrown when attempting to reconcile unbalanced journal entry lines.
 *
 * This exception is thrown when the sum of debits does not equal the sum of credits
 * in the selected journal entry lines for reconciliation.
 */
class UnbalancedReconciliationException extends ReconciliationException
{
    public function __construct(Money $totalDebits, Money $totalCredits, ?string $message = null)
    {
        $message = $message ?? sprintf(
            'Reconciliation is unbalanced. Total debits: %s, Total credits: %s, Difference: %s',
            $totalDebits->formatTo('en_US'),
            $totalCredits->formatTo('en_US'),
            $totalDebits->minus($totalCredits)->formatTo('en_US')
        );

        parent::__construct($message);
    }
}
