<?php

namespace App\Exceptions;

use Exception;

class DeletionNotAllowedException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $message The exception message.
     * @param int $code The exception code.
     */
    public function __construct(string $message = 'This record cannot be deleted due to business rules.', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
