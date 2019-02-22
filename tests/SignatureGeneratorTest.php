<?php

namespace HashedUriTest;

use HashedUri\Builder;
use HashedUri\Exception\HashedUriException;
use HashedUri\Exception\SignatureInvalidException;
use HashedUri\Exception\SignatureNotFoundException;
use HashedUri\Exception\TimeoutException;
use HashedUri\SignatureGenerator;
use HashedUri\Validator;
use HashedUriTest\Utility\ConcreteSignatureGenerator;
use HashedUriTest\Utility\ExtractionFailed;
use HashedUriTest\Utility\SampleUrlHashes;
use PHPUnit\Framework\TestCase;
use HashedUriTest\Utility\HashConfigFactory;
use HashedUri\HashConfiguration;

class SignatureGeneratorTest extends TestCase
{

    /** @var ConcreteSignatureGenerator */
    private $generator;

    protected function setUp()
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
        $hashedUrl = $builder->hashUrl($url);
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
        $hashedUrl = $builder->hashUrl($url);
        $this->assertTrue($validator->isValid($hashedUrl));
    }

    public function getSampleUrlsWithHash()
    {
        return SampleUrlHashes::getSampleHashes();
    }

}