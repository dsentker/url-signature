<?php

namespace UrlSignatureTest;

use UrlSignature\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;
use UrlSignatureTest\Utility\HashConfigFactory;
use UrlSignature\HashConfiguration;

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

    public function testHashConfigContainsSetBits()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $config->setHashMask(
            HashConfiguration::FLAG_HASH_SCHEME // 2
            | HashConfiguration::FLAG_HASH_HOST // 4
            | HashConfiguration::FLAG_HASH_PORT // 8
        );
        $this->assertTrue($config->hasHashConfigFlag(HashConfiguration::FLAG_HASH_SCHEME));
        $this->assertTrue($config->hasHashConfigFlag(HashConfiguration::FLAG_HASH_HOST));
        $this->assertTrue($config->hasHashConfigFlag(HashConfiguration::FLAG_HASH_PORT));

        foreach ([HashConfiguration::FLAG_HASH_PATH, HashConfiguration::FLAG_HASH_QUERY, HashConfiguration::FLAG_HASH_FRAGMENT] as $flag) {
            $this->assertFalse(
                $config->hasHashConfigFlag($flag),
                sprintf('The value "%d" must not match the bitmask "%d"!', $flag, $config->getHashMask())
            );
        }
    }

    public function testHashConfigWithArgumentUnpacking()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $config->setHashMask(
            HashConfiguration::FLAG_HASH_SCHEME // 2
            , HashConfiguration::FLAG_HASH_HOST // 4
            , HashConfiguration::FLAG_HASH_PORT // 8
        );
        $this->assertTrue($config->hasHashConfigFlag(HashConfiguration::FLAG_HASH_SCHEME));
        $this->assertTrue($config->hasHashConfigFlag(HashConfiguration::FLAG_HASH_HOST));
        $this->assertTrue($config->hasHashConfigFlag(HashConfiguration::FLAG_HASH_PORT));

        foreach ([HashConfiguration::FLAG_HASH_PATH, HashConfiguration::FLAG_HASH_QUERY, HashConfiguration::FLAG_HASH_FRAGMENT] as $flag) {
            $this->assertFalse(
                $config->hasHashConfigFlag($flag),
                sprintf('The value "%d" must not match the bitmask "%d"!', $flag, $config->getHashMask())
            );
        }
    }


    public function testSignatureAndTimeOutKeyMustBeDifferent()
    {
        $this->expectException(ConfigurationException::class);
        $config = new HashConfiguration('42', 'key', 'key');
    }

    public function testAlgorithmSetAndGet()
    {
        // As we not know the registered algorithms on this platforms, we use the first available.
        $registeredAlgos = hash_hmac_algos();
        if (empty($registeredAlgos)) {
            $this->markTestSkipped('No hash algorithm available on this platform.');
        }

        $algo = $registeredAlgos[0];
        $config = HashConfigFactory::createSimpleConfiguration();
        $config->setAlgorithm($algo);
        $this->assertEquals($algo, $config->getAlgorithm());

    }

    public function testExceptionOnNonExistentAlgorithm()
    {
        $this->expectException(ConfigurationException::class);
        $config = HashConfigFactory::createSimpleConfiguration();
        $config->setAlgorithm('I-Am-An-Nonexistent-Hash-Algorithm');
    }


}
