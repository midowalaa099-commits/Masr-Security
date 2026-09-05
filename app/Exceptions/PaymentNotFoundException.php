<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No matching payment found for the gateway callback.');
    }
}
