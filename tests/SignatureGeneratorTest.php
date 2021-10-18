<?php

namespace UrlSignatureTest;

use UrlSignature\Builder;
use UrlSignature\Exception\UrlSignatureException;
use UrlSignature\Exception\SignatureInvalidException;
use UrlSignature\Exception\SignatureNotFoundException;
use UrlSignature\Exception\TimeoutException;
use UrlSignature\SignatureGenerator;
use UrlSignature\Validator;
use UrlSignatureTest\Utility\ConcreteSignatureGenerator;
use UrlSignatureTest\Utility\ExtractionFailed;
use UrlSignatureTest\Utility\SampleUrlHashes;
use PHPUnit\Framework\TestCase;
use UrlSignatureTest\Utility\HashConfigFactory;
use UrlSignature\HashConfiguration;

class SignatureGeneratorTest extends TestCase
{

    /** @var ConcreteSignatureGenerator */
    private $generator;

    protected function setUp(): void
    {
        $this->generator = new ConcreteSignatureGenerator();
    }

    /**
     * @dataProvider getSampleUrlsWithHash
     */
    public function testGeneratedHashIsValid($url, $expectedHash)
    {
        $actualHash = $this->generator->getGeneratedSignatureFromUrl($url);
        $this->assertEquals($expectedHash, $actualHash);
    }

    /**
     * @dataProvider getSampleUrlsWithHash
     */
    public function testGenerationAndValidationWithSimpleConfiguration($url)
    {
        $simpleConfiguration = HashConfigFactory::createSimpleConfiguration();
        $builder = new Builder($simpleConfiguration);
        $validator = new Validator($simpleConfiguration);
        $hashedUrl = $builder->signUrl($url);
        $this->assertTrue($validator->isValid($hashedUrl));
    }

    /**
     * @dataProvider getSampleUrlsWithHash
     */
    public function testGenerationAndValidationWithAdvancedConfiguration($url)
    {
        $simpleConfiguration = HashConfigFactory::createAdvancedConfigurationWithFullHashFlags();
        $builder = new Builder($simpleConfiguration);
        $validator = new Validator($simpleConfiguration);
        $hashedUrl = $builder->signUrl($url);
        $this->assertTrue($validator->isValid($hashedUrl));
    }

    public function getSampleUrlsWithHash()
    {
        return SampleUrlHashes::getSampleHashes();
    }

}