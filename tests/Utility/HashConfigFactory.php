<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 21.02.2019
 * Time: 12:33
 */

namespace HashedUriTest\Utility;

use HashedUri\HashConfiguration;

class HashConfigFactory
{
    const SECRET = 'f00bar';

    const SIGNATURE_KEY = '_s';

    const TIMEOUT_KEY = '_t';

    const ALGORITHM = 'SHA256';

    /**
     * @return HashConfiguration
     */
    public static function createSimpleConfiguration()
    {
        $config = new HashConfiguration(static::SECRET, static::SIGNATURE_KEY, static::TIMEOUT_KEY);
        $config->setAlgorithm(static::ALGORITHM);
        return $config;
    }

    public static function createAdvancedConfigurationWithFullHashFlags()
    {
        $config = static::createSimpleConfiguration();
        $config->setHashConfig(HashConfiguration::FLAG_HASH_SCHEME
            | HashConfiguration::FLAG_HASH_HOST
            | HashConfiguration::FLAG_HASH_PORT
            | HashConfiguration::FLAG_HASH_PATH
            | HashConfiguration::FLAG_HASH_QUERY
            | HashConfiguration::FLAG_HASH_FRAGMENT
        );

        return $config;
    }
}