<?php

namespace App\Exceptions;

use Exception;

class UpdateNotAllowedException extends Exception
{
    public function __construct(string $message = 'This record cannot be updated.')
    {
        parent::__construct($message);
    }
}
