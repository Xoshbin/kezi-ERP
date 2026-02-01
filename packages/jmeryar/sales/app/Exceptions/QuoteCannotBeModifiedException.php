<?php

namespace Jmeryar\Sales\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to modify a quote that cannot be changed.
 */
class QuoteCannotBeModifiedException extends Exception
{
    public function __construct(string $message = 'This quote cannot be modified.')
    {
        parent::__construct($message);
    }
}
