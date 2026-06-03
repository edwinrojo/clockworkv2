<?php

namespace App\Exceptions;

use App\Enums\CheckInErrorCode;
use Exception;

class CheckInException extends Exception
{
    public function __construct(public CheckInErrorCode $errorCode)
    {
        parent::__construct($errorCode->message());
    }
}
