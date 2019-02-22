<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 22.02.2019
 * Time: 16:00
 */

namespace HashedUri\Exception;


class ConfigurationException extends HashedUriException
{
    public static function differentKeysRequired(?string $definedKey)
    {
        return new static(sprintf('The URL key "%s" was defined for the signature AND the timeout. The keys must be different.', $definedKey));

    }
}