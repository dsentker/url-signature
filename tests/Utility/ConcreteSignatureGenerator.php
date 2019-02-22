<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 22.02.2019
 * Time: 14:58
 */

namespace HashedUriTest\Utility;

use HashedUri\HashConfiguration;
use HashedUri\SignatureGenerator;
use function League\Uri\parse;

class ConcreteSignatureGenerator extends SignatureGenerator
{

    const KEY = 'secure-key';

    public function getConfiguration(): HashConfiguration
    {
        $config = new HashConfiguration(static::KEY);
        $config->setHashConfig(
            HashConfiguration::FLAG_HASH_SCHEME
            | HashConfiguration::FLAG_HASH_HOST
            | HashConfiguration::FLAG_HASH_PORT
            | HashConfiguration::FLAG_HASH_PATH
            | HashConfiguration::FLAG_HASH_QUERY
            | HashConfiguration::FLAG_HASH_FRAGMENT
        );

        return $config;
    }

    // Bypass the protected method getUrlSignature from abstract class
    public function getGeneratedSignatureFromUrl(string $url)
    {
        $urlComponents = parse($url);
        return $this->getUrlSignature($urlComponents);

    }


}