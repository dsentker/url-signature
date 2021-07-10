<?php

namespace UrlFingerprint\Exception;

final class InvalidHashAlgorithm extends \RuntimeException
{
    public static function hashUnknown(string $algo): InvalidHashAlgorithm
    {
        return new self(sprintf('Hash unknown: "%s"!', $algo));
    }
}