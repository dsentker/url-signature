<?php
/**
 * @noinspection PhpParamsInspection As method parameter annotations are not required for tests.
 * @noinspection PhpDocSignatureInspection  As PhpUnit expects a namespaced string scalar in PhpStorm
 */

namespace HashedUriTest;

use HashedUri\Builder;
use HashedUri\Exception\TimeoutException;
use HashedUriTest\Utility\ExtractionFailed;
use HashedUriTest\Utility\UrlQueryExtractor;
use PHPUnit\Framework\TestCase;
use HashedUriTest\Utility\HashConfigFactory;
use HashedUri\HashConfiguration;

/**
 * Class BuilderTest
 *
 * @package HashedUriTest
 */
class BuilderTest extends TestCase
{
    /** @var Builder */
    private $builder;

    protected function setUp()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $this->builder = new Builder($config);
    }

    public function testCanGetConfigObject()
    {
        $config = HashConfigFactory::createSimpleConfiguration();
        $builder = new Builder($config);
        $this->assertSame($builder->getConfiguration(), $config);
    }

    public function testConfigObjectIsNotShared()
    {
        $builder1 = new Builder(new HashConfiguration('42'));
        $builder2 = new Builder(new HashConfiguration('42'));

        $this->assertNotSame($builder1->getConfiguration(), $builder2->getConfiguration());
    }


    public function testHostExistsInUrl()
    {
        $url = 'https://www.example.com';
        $hashedUrl = $this->builder->hashUrl($url);
        $this->assertRegExp('#^https://www.example.com#', $hashedUrl);
    }

    public function testFragmentExistsInUrl()
    {
        $url = 'https://www.example.com/foo/#fragment';
        $hashedUrl = $this->builder->hashUrl($url);
        $this->assertRegExp('+#fragment$+', $hashedUrl);
    }

    public function testOriginalQueryStringIsKept()
    {
        $url = 'https://www.example.com/?foo=bar&qux=1234';
        $hashedUrl = $this->builder->hashUrl($url);
        $this->assertRegExp('+\?foo=bar&qux=1234+', $hashedUrl);
    }

    public function testQueryStringContainsSignature()
    {
        $url = 'https://www.example.com/?foo=bar&qux=1234';
        $hashedUrl = $this->builder->hashUrl($url);

        // Assuming a Sha256 hash is used (consists of 64 hex characters)
        $expectedPattern = sprintf('~%s=[A-Fa-f0-9]{64}~', $this->builder->getConfiguration()->getSignatureUrlKey());

        $this->assertRegExp($expectedPattern, $hashedUrl, sprintf('The URL "%s" does not match expected pattern "%s"!', $hashedUrl, $expectedPattern));
    }


    /**
     * @dataProvider getTestUrlsWithHash
     */
    public function testSignatureHash(string $pathAndQuery, string $expectedHash)
    {

        // Create a new config to prevent mismatch due to external changes on the utility configuration object.
        $config = new HashConfiguration('secure-key');
        $config->setAlgorithm('SHA256');
        $config->setHashConfig(HashConfiguration::FLAG_HASH_PATH | HashConfiguration::FLAG_HASH_QUERY);
        $builder = new Builder($config);
        $url = sprintf('https://example.com%s', $pathAndQuery);
        $hashedUrl = $builder->hashUrl($url);

        $expectedPattern = sprintf('~%s=%s~', $builder->getConfiguration()->getSignatureUrlKey(), $expectedHash);

        #$message = sprintf('The URL "%s" does not match expected pattern "%s"!', $hashedUrl, $expectedPattern);
        $message = sprintf('The URL "%s" expected a hash which does not match the hash for the URL "%s"!', $pathAndQuery, $builder->lastHashedUrl);
        $this->assertRegExp($expectedPattern, $hashedUrl, $message);


    }


    public function testHashUrlFromInvalidUrl()
    {
        $url = 'goose@fraba.de';
        $hashedUrl = $this->builder->hashUrl($url);
        $this->assertRegExp('~^goose@fraba.de~', $hashedUrl);

    }

    public function testUrlsWithoutHost()
    {
        // Create a new config to prevent mismatch due to external changes on the utility configuration object.
        $config = new HashConfiguration('secure-key');
        $config->setAlgorithm('SHA256');
        $config->setHashConfig(HashConfiguration::FLAG_HASH_HOST | HashConfiguration::FLAG_HASH_PATH); // check host with path
        $builder = new Builder($config);
        $hashedUrl = $builder->hashUrl('/foo/bar?baz'); // host is missing

        $this->assertRegExp('#^\/foo\/bar\?baz#', $hashedUrl);

    }

    public function testEmptyUrl()
    {
        $hashedUrl = $this->builder->hashUrl('/'); // no host, no path, no query
        $this->assertRegExp(sprintf('#^\/\?%s=#', $this->builder->getConfiguration()->getSignatureUrlKey()), $hashedUrl);

    }

    /**
     * @dataProvider getValidTimeoutValues
     */
    public function testValidTimeOut($timeout, int $expected)
    {
        $hashedUrl = $this->builder->hashUrl('/', $timeout);

        try {
            $timeout = UrlQueryExtractor::extractTimeoutFormUrl($this->builder->getConfiguration(), $hashedUrl);
        } catch (ExtractionFailed $e) {
            /** @noinspection PhpParamsInspection */
            $this->fail($e->getMessage());
        }


        $this->assertEquals($expected, $timeout, sprintf('Url "%s" should have the timeut value "%s", got "%s" instead.', $hashedUrl, $expected, $timeout));


    }

    /**
     * @dataProvider getInvalidTimeoutValues
     */
    public function testInvalidTimeOut($timeoutValue)
    {
        $this->expectException(TimeoutException::class);
        $hashedUrl = $this->builder->hashUrl('/', $timeoutValue);
    }

    public function testModifiedSignature()
    {
        $hashedUrl = $this->builder->hashUrl('/foo', '+42 seconds');
        $originalSignature = UrlQueryExtractor::extractSignatureFormUrl($this->builder->getConfiguration(), $hashedUrl);
        $originalTimeout = UrlQueryExtractor::extractTimeoutFormUrl($this->builder->getConfiguration(), $hashedUrl);

        $updatedHashedUrl = $hashedUrl . '#bar'; // add fragment which is not used by hash.
        $updatedSignature = UrlQueryExtractor::extractSignatureFormUrl($this->builder->getConfiguration(), $updatedHashedUrl);
        $updatedTimeout = UrlQueryExtractor::extractTimeoutFormUrl($this->builder->getConfiguration(), $updatedHashedUrl);

        $this->assertEquals($originalSignature, $updatedSignature);
        $this->assertEquals($originalTimeout, $updatedTimeout);
    }

    public function testModifiedSignatureAndTimeout()
    {
        $hashedUrl = $this->builder->hashUrl('/foo', '+42 seconds');
        $originalSignature = UrlQueryExtractor::extractSignatureFormUrl($this->builder->getConfiguration(), $hashedUrl);
        $originalTimeout = UrlQueryExtractor::extractTimeoutFormUrl($this->builder->getConfiguration(), $hashedUrl);

        // Hash URL again with updated timeout
        $updatedHashedUrl = $this->builder->hashUrl($hashedUrl, '+10 minutes');
        $updatedSignature = UrlQueryExtractor::extractSignatureFormUrl($this->builder->getConfiguration(), $updatedHashedUrl);
        $updatedTimeout = UrlQueryExtractor::extractTimeoutFormUrl($this->builder->getConfiguration(), $updatedHashedUrl);

        $this->assertNotEquals($originalSignature, $updatedSignature);
        $this->assertNotEquals($originalTimeout, $updatedTimeout);
    }

    public function testSignatureIsSameOnDifferentProtocols()
    {
        $hashedUrlWithHttp = $this->builder->hashUrl('http://www.example.com/foo');
        $httpSignature = UrlQueryExtractor::extractSignatureFormUrl($this->builder->getConfiguration(), $hashedUrlWithHttp);
        $hashedUrlWithHttps = $this->builder->hashUrl('https://www.example.com/foo');
        $httpsSignature = UrlQueryExtractor::extractSignatureFormUrl($this->builder->getConfiguration(), $hashedUrlWithHttps);

        $this->assertEquals($httpSignature, $httpsSignature);

    }

    public function testSignatureIsDifferentOnDifferentProtocols()
    {

        $config = HashConfigFactory::createSimpleConfiguration();
        $config->setHashConfig(HashConfiguration::FLAG_HASH_SCHEME | HashConfiguration::FLAG_HASH_PATH);
        $builder = new Builder($config);

        $hashedUrlWithHttp = $builder->hashUrl('http://www.example.com/foo');
        $httpSignature = UrlQueryExtractor::extractSignatureFormUrl($this->builder->getConfiguration(), $hashedUrlWithHttp);
        $hashedUrlWithHttps = $builder->hashUrl('https://www.example.com/foo');
        $httpsSignature = UrlQueryExtractor::extractSignatureFormUrl($this->builder->getConfiguration(), $hashedUrlWithHttps);

        $this->assertNotEquals($httpSignature, $httpsSignature);

    }

    // Data provider below
    // ------------------------------------------------------------------------
    //



    public function getValidTimeoutValues()
    {
        return [
            [new \DateTime('10.10.2035 10:10:10', new \DateTimeZone('Europe/Berlin')), 2075616610], // DateTime Object
            ['10.10.2035 10:10:10', 2075616610],                // strtotime()
            [1893492672, 1893492672],                           // native integer
        ];

    }

    public function getInvalidTimeoutValues()
    {

        $toStringClass = new class
        {
            public function __toString()
            {
                return (string)(new \DateTime('2035-10-10 10:10:10'))->getTimestamp();
            }
        };

        $callable = function () {
            return (string)(new \DateTime('2035-10-10 10:10:10'))->getTimestamp();
        };

        return [
            [new \stdClass()],                            // invalid class
            [$toStringClass],                             // class with __toString, but not supported by builder
            [$callable],                                  // callable, not supported by builder
            [[42]],                                       // invalid array
            ['qux'],                                      // not readable by strtotime()
            [new \DateTime('2001-10-10 10:10:10')],   // DateTime in past
            ['-10 seconds'],                              // readable by strotime(), but in past
            [1235299210],                                 // Valid timestamp, but in past
        ];
    }

    public function getTestUrlsWithHash()
    {
        # To test yourself:
        # var_dump(hash_hmac('SHA256', '', 'secure-key')); // fb733dd1c218a508557e5c1f175099d2109cef323279c9e890c15e8e8efa0a9e
        # var_dump(hash_hmac('SHA256', '/', 'secure-key')); // d603a7eee64f1e0f9bc9388a7fdf18ebddab6c5676220b613a7f6f3c90a9ebfc
        # var_dump(hash_hmac('SHA256', '/test', 'secure-key')); // fbdd0b5c0d62dd16deb3111bf81fa97d31441b8fa369aa250819f42caafdbd40
        # var_dump(hash_hmac('SHA256', '/foo/bar?qux=pax', 'secure-key')); // 0a186b0712502fa25c85acc7c563f7fe9c9e2fdbd73e2de5897fc79eb1b05c5e
        # var_dump(hash_hmac('SHA256', '/foo?qux&baz=bar', 'secure-key')); // 3be37e183563da4da2de7ecdea1ab5d1fdfdecd7a7b0fe7cd93dd0bab09acf1f

        return [
            ['', 'fb733dd1c218a508557e5c1f175099d2109cef323279c9e890c15e8e8efa0a9e'],
            ['/', 'd603a7eee64f1e0f9bc9388a7fdf18ebddab6c5676220b613a7f6f3c90a9ebfc'],
            ['/test', 'fbdd0b5c0d62dd16deb3111bf81fa97d31441b8fa369aa250819f42caafdbd40'],
            ['/foo/bar?qux=pax', '0a186b0712502fa25c85acc7c563f7fe9c9e2fdbd73e2de5897fc79eb1b05c5e'],
            ['/foo?qux&baz=bar', '3be37e183563da4da2de7ecdea1ab5d1fdfdecd7a7b0fe7cd93dd0bab09acf1f'],
            ['/foo?qux&baz=bar#fragment', '3be37e183563da4da2de7ecdea1ab5d1fdfdecd7a7b0fe7cd93dd0bab09acf1f'], // same hash as before, fragment must be ignored
        ];
    }

}