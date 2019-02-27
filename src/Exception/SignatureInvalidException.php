<?php

namespace UrlSignature\Exception;

class SignatureInvalidException extends ValidationException
{

    public static function emptySignature(?string $givenSignature): SignatureInvalidException
    {
        return new static(sprintf('The Signature "%s" is invalid.', $givenSignature));
    }
}