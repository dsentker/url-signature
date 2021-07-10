<?php

namespace UrlHasher\Exception;

class InvalidHashAlgorithm extends \RuntimeException
{
    public static function hashUnknown(string $algo)
    {
        return new self(sprintf('Hash unknown: "%s"!', $algo));
    }
}