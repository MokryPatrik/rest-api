<?php

namespace App\Exception;

use RuntimeException;

class UniqueConstraintException extends RuntimeException
{
    public function __construct(string $message = "", int $code = 0)
    {
        parent::__construct($message, $code);
    }
} 