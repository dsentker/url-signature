<?php

namespace UrlSignature\Exception;

class SignatureExpiredException extends ValidationException
{
    public static function timeoutViolation(): SignatureExpiredException
    {
        return new static('Signature has expired and is no longer valid!');
    }
}