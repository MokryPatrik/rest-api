<?php

namespace App\Exception;

class ProductNotFoundException extends \RuntimeException
{
    public function __construct(string $message = "", int $code = 0)
    {
        parent::__construct($message, $code);
    }
} 