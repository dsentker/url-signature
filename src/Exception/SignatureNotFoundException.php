<?php

namespace UrlSignature\Exception;

class SignatureNotFoundException extends ValidationException
{
    public static function notPresetInQueryString(?string $givenQuery): SignatureNotFoundException
    {
        return (empty($givenQuery))
            ? new static(sprintf('Can not verify the URL because it does not contain a query string'))
            : new static(sprintf(
                'Can not verify the URL because it does not contain a signature in query string "%s".',
                $givenQuery
            ));
    }
}
