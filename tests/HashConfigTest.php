<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 21.02.2019
 * Time: 11:56
 */

namespace HashedUriTest;

use HashedUri\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;
use HashedUriTest\Utility\HashConfigFactory;
use HashedUri\HashConfiguration;

class HashConfigTest extends TestCase
{


    public function testGetAlgorithm()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $this->assertEquals(HashConfigFactory::ALGORITHM, $config->getAlgorithm());

    }

    public function testGetKey()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $this->assertEquals(HashConfigFactory::SECRET, $config->getKey());

    }

    public function testGetTimeoutUrlKey()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $this->assertEquals(HashConfigFactory::TIMEOUT_KEY, $config->getTimeoutUrlKey());
    }

    public function testGetSignatureUrlKey()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $this->assertEquals(HashConfigFactory::SIGNATURE_KEY, $config->getSignatureUrlKey());
    }

    public function testHashConfigWithBit1()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $config->setHashConfig(HashConfiguration::FLAG_HASH_SCHEME);
        $this->assertTrue($config->hasHashConfigFlag(HashConfiguration::FLAG_HASH_SCHEME));

        foreach([4, 8, 0] as $flag) {
            $this->assertFalse($config->hasHashConfigFlag($flag), sprintf('The value "%s" must not match the bitwise mask "%d"!', $flag, HashConfiguration::FLAG_HASH_SCHEME));
        }
    }

    public function testHashConfigWithBit16()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $config->setHashConfig(HashConfiguration::FLAG_HASH_QUERY);
        $this->assertTrue($config->hasHashConfigFlag(HashConfiguration::FLAG_HASH_QUERY));

        foreach([4, 8, 0] as $flag) {
            $this->assertFalse($config->hasHashConfigFlag($flag), sprintf('The value "%s" must not match the bitwise mask "%d"!', $flag, HashConfiguration::FLAG_HASH_QUERY));
        }
    }

    public function testSignatureAndTimeOutKeyMustBeDifferent()
    {
        $this->expectException(ConfigurationException::class);
        $config = new HashConfiguration('42', 'key', 'key');
    }


}
