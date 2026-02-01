<?php

namespace Jmeryar\Accounting\Exceptions\Reconciliation;

/**
 * Exception thrown when attempting reconciliation while the feature is globally disabled.
 *
 * This exception is thrown when a user attempts to perform any reconciliation operation
 * while the company's enable_reconciliation setting is false.
 */
class ReconciliationDisabledException extends ReconciliationException
{
    public function __construct(string $message = 'Reconciliation functionality is disabled for this company.')
    {
        parent::__construct($message);
    }
}
