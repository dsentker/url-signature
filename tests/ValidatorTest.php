<?php

namespace UrlSignatureTest;

use UrlSignature\Builder;
use UrlSignature\Exception\UrlSignatureException;
use UrlSignature\Exception\SignatureExpiredException;
use UrlSignature\Exception\SignatureInvalidException;
use UrlSignature\Exception\SignatureNotFoundException;
use UrlSignature\Exception\TimeoutException;
use UrlSignature\QueryString;
use UrlSignature\Validator;
use UrlSignatureTest\Utility\ExtractionFailed;
use UrlSignatureTest\Utility\SampleUrlHashes;
use function League\Uri\build;
use function League\Uri\parse;
use PHPUnit\Framework\TestCase;
use UrlSignatureTest\Utility\HashConfigFactory;
use UrlSignature\HashConfiguration;

class ValidatorTest extends TestCase
{
    /** @var Validator */
    private $validator;

    protected function setUp()
    {
        $config = HashConfigFactory::createAdvancedConfigurationWithFullHashFlags();
        $this->validator = new Validator($config);
    }

    /**
     * @group failing
     * @group issue-1
     */
    public function testExpirationIsValid()
    {
        $builder = new Builder(HashConfigFactory::createSimpleConfiguration());
        $hashedUrl = $builder->signUrl('https://example.com/foo/', '+10 minutes');
        $this->assertTrue($builder->createValidator()->verify($hashedUrl));
    }

    public function testExpirationIsInvalid()
    {
        $this->expectException(SignatureExpiredException::class);

        $builder = new Builder(HashConfigFactory::createSimpleConfiguration());
        $hashedUrl = $builder->signUrl('http://example.com/foo', '+1 seconds');
        usleep(2010000);
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

    public function testExceptionOnWrongSignature()
    {
        $this->expectException(SignatureInvalidException::class);
        $hashedUrl = $this->addSignatureToUrl('http://www.example.com', 'iCertainlyWillNotMatch');
        $this->validator->verify($hashedUrl);
    }

    // Data Provider and helper methods Below
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Adds a hash to the query string of $url
     *
     * @param string $url
     * @param string $urlHash
     *
     * @return string
     */
    private function addSignatureToUrl(string $url, string $urlHash)
    {
        // Add signature to URL
        $urlComponents = parse($url);
        $queryParts = QueryString::getKeyValuePairs($urlComponents['query']);
        $queryParts[$this->validator->getConfiguration()->getSignatureUrlKey()] = $urlHash;
        $urlComponents['query'] = QueryString::build($queryParts);
        return build($urlComponents);
    }

    public function getSampleUrlsWithHash()
    {
        return SampleUrlHashes::getSampleHashes();
    }


}