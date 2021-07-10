<?php

namespace UrlFingerprint\Exception;

use League\Uri\Exceptions\SyntaxError;

class InvalidUrl extends \InvalidArgumentException
{
    public static function schemeIsMissing(string $url): InvalidUrl
    {
        return new self(sprintf('The scheme for url (%s) is missing!', $url));
    }

    public static function syntaxError(SyntaxError $syntaxError): InvalidUrl
    {
        return new self($syntaxError->getMessage());
    }

    public static function isEmpty(): InvalidUrl
    {
        return new self('The URL string is empty!');
    }
}