<?php

namespace App\Exceptions;

use Exception;

class PublicException extends Exception
{
    public function __construct($message = "proses belum bisa di lanjutkan, silahkan coba beberapa saat lagi", $code = 400)
    {
        parent::__construct($message, $code);
    }
}
