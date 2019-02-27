<?php

namespace UrlSignature\Exception;

class ConfigurationException extends UrlSignatureException
{

    public static function differentKeysRequired(?string $definedKey)
    {
        return new static(sprintf('The URL key "%s" was defined for the signature AND the timeout. The keys must be different.', $definedKey));
    }

    public static function invalidAlgorithm(string $algo, array $availableAlgos)
    {
        return new static(sprintf('The hash algorithm "%s" is not available on this platform. Use one of the registered hashing algorithms: "%s".', $algo, implode(', ', $availableAlgos)));
    }
}