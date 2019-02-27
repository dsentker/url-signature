<?php

namespace UrlSignature\Exception;

class TimeoutException extends UrlSignatureException
{
    public static function notParsable()
    {
        return new self('The timeout cannot be parsed via strtotime() and is evidently not a valid date format (as defined via http://php.net/manual/de/datetime.formats.php)');
    }

    public static function unknownFormat($given)
    {
        return new self(sprintf('Unknown timeout type given: "%s" (expected: int|string|\DateTimeInterface)!', gettype($given)));
    }

    public static function notValid(string $message)
    {
        return new self(sprintf('The timeout is not valid: %s', $message));
    }
}