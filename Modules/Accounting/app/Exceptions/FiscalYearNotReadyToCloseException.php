<?php

namespace Modules\Accounting\Exceptions;

use Exception;

class FiscalYearNotReadyToCloseException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Fiscal year is not ready to be closed.')
    {
        parent::__construct($message);
    }
}
