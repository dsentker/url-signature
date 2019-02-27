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

    public function testHashConfigWithBit1()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $config->setHashMask(HashConfiguration::FLAG_HASH_SCHEME);
        $this->assertTrue($config->hasHashConfigFlag(HashConfiguration::FLAG_HASH_SCHEME));

        foreach([4, 8, 0] as $flag) {
            $this->assertFalse($config->hasHashConfigFlag($flag), sprintf('The value "%s" must not match the bitwise mask "%d"!', $flag, HashConfiguration::FLAG_HASH_SCHEME));
        }
    }

    public function testHashConfigWithBit16()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $config->setHashMask(HashConfiguration::FLAG_HASH_QUERY);
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

    public function testAlgorithmSetAndGet()
    {
        // As we not know the registered algorithms on this platforms, we use the first available.
        $registeredAlgos = hash_hmac_algos ();
        if(empty($registeredAlgos)) {
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
