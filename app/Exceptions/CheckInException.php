<?php

namespace App\Exceptions;

use App\Enums\CheckInErrorCode;
use Exception;

class CheckInException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public CheckInErrorCode $errorCode,
        public array $context = [],
    ) {
        parent::__construct($errorCode->message());
    }
}
