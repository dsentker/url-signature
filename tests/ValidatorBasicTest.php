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

class ValidatorBasicTest extends TestCase
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


}