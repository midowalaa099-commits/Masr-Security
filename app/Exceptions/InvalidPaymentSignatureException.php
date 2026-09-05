<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidPaymentSignatureException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid payment gateway signature.');
    }
}
