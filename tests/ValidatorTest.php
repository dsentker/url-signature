<?php

namespace HashedUriTest;

use HashedUri\Builder;
use HashedUri\Exception\HashedUriException;
use HashedUri\Exception\SignatureExpiredException;
use HashedUri\Exception\SignatureInvalidException;
use HashedUri\Exception\SignatureNotFoundException;
use HashedUri\Exception\TimeoutException;
use HashedUri\QueryString;
use HashedUri\Validator;
use HashedUriTest\Utility\ExtractionFailed;
use HashedUriTest\Utility\SampleUrlHashes;
use function League\Uri\build;
use function League\Uri\parse;
use PHPUnit\Framework\TestCase;
use HashedUriTest\Utility\HashConfigFactory;
use HashedUri\HashConfiguration;

class ValidatorTest extends TestCase
{
    /** @var Validator */
    private $validator;

    protected function setUp()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $this->validator = new Validator($config);
    }

    public function testCanGetConfigObject()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $validator = new Validator($config);
        $this->assertSame($validator->getConfiguration(), $config);
    }

    public function testConfigObjectIsNotShared()
    {
        $validator1 = new Validator(new HashConfiguration('42'));
        $validator2 = new Validator(new HashConfiguration('42'));

        $this->assertNotSame($validator1->getConfiguration(), $validator2->getConfiguration());
    }

    public function testExceptionOnMissingSignature()
    {
        #$builder = new Builder($this->validator->getConfiguration());
        #$hashedUrl = $builder->hashUrl('https://example.com');

        $this->expectException(SignatureNotFoundException::class);
        $this->validator->verify('https://example.com');

    }

    /**
     * @dataProvider getEmptySignatureUrls
     */
    public function testExceptionOnEmptySignature(string $url, string $expectedExceptionClass)
    {
        $this->expectException($expectedExceptionClass);
        $this->validator->verify($url);

    }

    public function testExpiredUrl()
    {
        $this->expectException(SignatureExpiredException::class);

        $builder = new Builder(HashConfigFactory::createSimpleConfiguration());
        $hashedUrl = $builder->hashUrl('http://example.com/foo', '+1 seconds');
        sleep(2); // sorry for that.
        $builder->createValidator()->verify($hashedUrl);

    }


    /**
     * @dataProvider getSampleUrlsWithHash
     */
    public function testValidSignatureIsValidated($url, $urlHash)
    {

        $hashedUrl = $this->addSignatureToUrl($url, $urlHash);
        $this->assertTrue($this->validator->isValid($hashedUrl));

    }

    // Data Provider and helper methods Below
    // -----------------------------------------------------------------------------------------------------------------
    //


    /**
     * Adds a hash to the query string of $url
     *
     * @param string $url
     * @param string $urlHash
     *
     * @return string
     */
    private function addSignatureToUrl(string $url, string $urlHash) {
        // Add signature to URL
        $urlComponents = parse($url);
        $queryParts = QueryString::getKeyValuePairs($urlComponents['query']);
        $queryParts[$this->validator->getConfiguration()->getSignatureUrlKey()] = $urlHash;
        $urlComponents['query'] = QueryString::build($queryParts);
        return build($urlComponents);
    }


    public function getEmptySignatureUrls()
    {
        $signatureKey = HashConfigFactory::createSimpleConfiguration()->getSignatureUrlKey();
        return [
            [sprintf('https://example.com/foo'), SignatureNotFoundException::class],
            [sprintf('https://example.com/foo?'), SignatureNotFoundException::class],
            [sprintf('https://example.com/%s', $signatureKey), SignatureNotFoundException::class],
            [sprintf('https://example.com/foo?%s', $signatureKey), SignatureInvalidException::class],
            [sprintf('https://example.com/foo?%s=', $signatureKey), SignatureInvalidException::class],
            [sprintf('https://example.com/foo?%s=0', $signatureKey), SignatureInvalidException::class],
            [sprintf('https://example.com/foo?%s=&x', $signatureKey), SignatureInvalidException::class],
        ];
    }

    public function getSampleUrlsWithHash()
    {
        return SampleUrlHashes::getSampleHashes();
    }


}