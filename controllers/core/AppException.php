<?php

class AppException extends Exception
{
    public function __construct(string $message, int $httpCode = 400)
    {
        parent::__construct($message, $httpCode);
    }
}
