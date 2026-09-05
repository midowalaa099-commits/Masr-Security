<?php

namespace App\Exceptions;

use RuntimeException;

class CartEmptyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The cart is empty.');
    }
}
