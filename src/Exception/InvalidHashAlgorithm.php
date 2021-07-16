<?php

namespace UrlFingerprint\Exception;

final class InvalidHashAlgorithm extends \RuntimeException
{
    public static function unknownAlgorithm(string $algo): InvalidHashAlgorithm
    {
        return new self(sprintf('Hash algorithm unknown: "%s"!', $algo));
    }
}