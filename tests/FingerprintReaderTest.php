<?php

namespace UrlFingerprintTest;

use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use UrlFingerprint\Exception\InvalidUrl;
use UrlFingerprint\FingerprintReader;

class FingerprintReaderTest extends TestCase
{
    public function testHashAlgoIsRecognized()
    {
        $reader = new FingerprintReader([
            'secret'    => '42',
            'hash_algo' => 'md5',
        ]);
        $urlHash = $reader->capture('https://www.example.com');

        $this->assertEquals('md5', $urlHash->hashAlgo);
    }

    /**
     * @dataProvider getUrlsWithQueryToSort
     */
    public function testQueryStringIsSorted(string $expected, string $url, string $message = null)
    {
        $reader = new FingerprintReader([
            'secret' => '42',
        ]);
        $urlHash = $reader->capture($url);

        $this->assertEquals(
            $expected,
            $urlHash->gist,
            $message
        );
    }

    public function testUrlEncodedCharacters()
    {
        $reader = new FingerprintReader([
            'secret'        => '42',
            'hash_fragment' => true,
        ]);
        $urlHash1 = $reader->capture('http://example.com/x.html?string=With%20Space%2BPlus');
        $urlHash2 = $reader->capture('http://example.com/x.html?string=With Space+Plus');

        $this->assertSame(
            $urlHash1->gist,
            $urlHash2->gist,
            'The fingerpint should honor special characters like %20 in the URL'
        );

        $this->assertSame(
            $urlHash1->digest,
            $urlHash2->digest,
            'The digest should be same even if url contain characters which should be encoded like "%20"'
        );
    }

    /**
     * @dataProvider getHashOptionsAndExpectedUrls
     */
    public function testHashOptionsAreHonored(string $urlToTest, array $hashOptions, string $expectedSkeleton)
    {
        $reader = new FingerprintReader(array_merge([
            'secret'    => '42',
            'hash_algo' => 'md5',
        ], $hashOptions));
        $urlHash = $reader->capture($urlToTest);

        $this->assertEquals(
            $expectedSkeleton,
            $urlHash->gist,
        );
    }

    public function testHashLengthIsValid()
    {
        $reader = new FingerprintReader([
            'secret'    => '42',
            'hash_algo' => 'md5',
        ]);
        $fingerprint = $reader->capture('https://www.example.com');

        $this->assertIsString($fingerprint->digest);
        $this->assertEquals(32, strlen($fingerprint->digest));
    }

    public function testMissingRequiredScheme()
    {
        $this->expectException(InvalidUrl::class);
        $this->expectExceptionMessage('The scheme for url (//www.example.com) is missing!');
        $readerEnforcingScheme = new FingerprintReader([
            'secret'      => '42',
            'hash_algo'   => 'md5',
            'hash_scheme' => true,
        ]);
        $readerEnforcingScheme->capture('//www.example.com');
    }

    public function testMissingScheme()
    {
        $reader = new FingerprintReader([
            'secret'      => '42',
            'hash_algo'   => 'md5',
            'hash_scheme' => false,
        ]);
        $fingerprint = $reader->capture('//www.example.com');

        $this->assertEquals(
            '{"hash_scheme":null,"hash_userinfo":null,"hash_host":"www.example.com","hash_port":null,"hash_path":"","hash_query":null,"hash_fragment":null}',
            $fingerprint->gist
        );
    }

    public function testEmptyUri()
    {
        $this->expectException(InvalidUrl::class);
        $this->expectExceptionMessage('The URL string is empty!');
        $fingerprintReader = new FingerprintReader([
            'secret'      => '42',
            'hash_algo'   => 'md5',
            'hash_scheme' => true,
        ]);
        $fingerprintReader->capture('');
    }

    public function testEmptyCharactersSurroundingUrlWillNotAffectTheResult()
    {
        $reader = new FingerprintReader([
            'secret'        => '42',
            'hash_algo'     => 'md5',
            'hash_scheme'   => true,
            'hash_fragment' => true,
        ]);
        $urlHash = $reader->capture(' https://www.example.com/#anchor ');

        $this->assertEquals(
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"www.example.com","hash_port":null,"hash_path":"/","hash_query":null,"hash_fragment":"anchor"}',
            $urlHash->gist
        );
    }

    public function testUrlWithSyntaxError()
    {
        $this->expectException(InvalidUrl::class);
        $this->expectExceptionMessage('The uri `https://` is invalid for the `https` scheme.');
        $reader = new FingerprintReader([
            'secret'      => '42',
            'hash_algo'   => 'md5',
            'hash_scheme' => true,
        ]);
        $reader->capture('https://');
    }

    public function testUrlPathWithWhitespace()
    {
        $reader = new FingerprintReader([
            'secret'      => '42',
            'hash_algo'   => 'md5',
            'hash_scheme' => false,
        ]);
        $urlHash = $reader->capture('//example.com/foo bar/baz');
        $this->assertEquals(
            '{"hash_scheme":null,"hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/foo%20bar/baz","hash_query":null,"hash_fragment":null}',
            $urlHash->gist
        );
    }

    public function testCompareFingerprints()
    {
        $reader = new FingerprintReader([
            'secret'        => '42',
            'hash_algo'     => 'md5',
            'hash_scheme'   => false,
            'hash_query'    => false,
            'hash_fragment' => false,
        ]);
        $urlHash1 = $reader->capture('http://www.example.com/foo/bar/?qux=baz');
        $urlHash2 = $reader->capture('https://www.example.com/foo/bar/#anchor');

        $this->assertTrue(
            $reader->compare($urlHash1, $urlHash2),
            sprintf('Assert that gist %s%s equals gist %s%s.', PHP_EOL, $urlHash1->gist, PHP_EOL,
                $urlHash2->gist)
        );
    }

    public function testCompareFingerprintsShouldPassWithDifferentQueryParameterOrder()
    {
        $reader = new FingerprintReader([
            'secret'     => '42',
            'hash_algo'  => 'md5',
            'hash_query' => true,
        ]);
        $urlHash1 = $reader->capture('https://www.example.com/foo/?ananas=baz&banana=qux&citrus');
        $urlHash2 = $reader->capture('https://www.example.com/foo/?citrus&banana=qux&ananas=baz');

        $this->assertTrue(
            $reader->compare($urlHash1, $urlHash2),
            sprintf('Assert that gist %s%s equals gist %s%s.', PHP_EOL, $urlHash1->gist, PHP_EOL,
                $urlHash2->gist)
        );
    }

    public function testCompareFingerprintsShouldFailWithDifferentQueryValues()
    {
        $reader = new FingerprintReader([
            'secret'     => '42',
            'hash_algo'  => 'md5',
            'hash_query' => true,
        ]);
        $urlHash1 = $reader->capture('https://www.example.com/foo/?ananas=&banana=qux&citrus');
        $urlHash2 = $reader->capture('https://www.example.com/foo/?citrus&banana=qux&ananas=baz');

        $this->assertFalse(
            $reader->compare($urlHash1, $urlHash2),
            sprintf('Assert that gist %s%s is not equal to gist %s%s.', PHP_EOL, $urlHash1->gist, PHP_EOL,
                $urlHash2->gist)
        );
    }

    public function testExceptionIsThrownWithInvalidHashAlgo()
    {
        $this->expectException(InvalidOptionsException::class);
        new FingerprintReader([
            'secret'    => '42',
            'hash_algo' => 'iDoNotExist',
        ]);
    }

    /**
     * @dataProvider getUrlsWithQueryParametersToIgnore
     */
    public function testQueryParameterAreIgnored(string $expectedUrl, string $actualUrl, array $ignore)
    {
        $reader = new FingerprintReader([
            'secret' => '42',
        ]);

        $expectedFingerprint = $reader->capture($expectedUrl);
        $actualFingerprint = $reader->capture($actualUrl, $ignore);

        $this->assertTrue($reader->compare($expectedFingerprint, $actualFingerprint),

            sprintf('%s%s%s', $expectedFingerprint->gist, PHP_EOL, $actualFingerprint->gist)
        );
    }

    public function getUrlsWithQueryParametersToIgnore()
    {
        return [
            [
                'https://www.example.com/path?foo=baz',
                'https://www.example.com/path?foo=baz&qux=faz',
                ['qux'],
            ],
            [
                'https://www.example.com/path?',
                'https://www.example.com/path?foo=baz&qux=faz',
                ['foo', 'qux'],
            ],
            [
                'https://www.example.com/path',
                'https://www.example.com/path?foo=baz&qux=faz',
                ['foo', 'qux'],
            ],
            [
                'https://www.example.com/path?foo=baz',
                'https://www.example.com/path?foo=baz&qux=faz&qux=faz2',
                ['qux'],
            ],
            [
                'https://www.example.com/path?foo=baz',
                'https://www.example.com/path?foo=baz&qux[]=faz',
                ['qux'],
            ],
            [
                'https://www.example.com/path?foo=baz',
                'https://www.example.com/path?foo=baz&qux[deep][nest]=faz',
                ['qux'],
            ],
            [
                'https://www.example.com/path?foo=baz',
                'https://www.example.com/path?foo=baz&qux[][]=faz',
                ['qux'],
            ],
            [
                'https://www.example.com/path?foo=baz',
                'https://www.example.com/path?foo=baz',
                ['FOO'],
            ],
            [
                'https://www.example.com/path?foo',
                'https://www.example.com/path?foo&baz',
                ['baz'],
            ],
            [
                'https://www.example.com/path?foo',
                'https://www.example.com/path?foo&baz#fragment',
                ['baz', true, 42, null,],
            ],
        ];
    }

    public function getUrlsWithQueryToSort(): iterable
    {
        yield [
            '{"hash_scheme":"http","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"","hash_query":null,"hash_fragment":null}',
            'http://example.com',
            'A URL without query string must not be sorted',
        ];
        yield [
            '{"hash_scheme":"http","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"","hash_query":null,"hash_fragment":null}',
            'http://example.com?',
            'A question mark in URL (no slash) without query string should have no effect',
        ];
        yield [
            '{"hash_scheme":"http","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":null,"hash_fragment":null}',
            'http://example.com/?',
            'A question mark in URL without query string should have no effect',
        ];
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":"foo=bar","hash_fragment":null}',
            'https://example.com/?foo=bar',
            'A URL with a single query key-value pair should not be sorted',
        ];
        // Sort is done properly / b=x&a=x will transform to a=x&b=x
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":"a=1337&b=42","hash_fragment":null}',
            'https://example.com/?b=42&a=1337',
            'The query string keys should be sorted ASC.',
        ];
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":"a=1337&b=42","hash_fragment":null}',
            'https://example.com/?a=1337&b=42',
            'Query string keys already sorted should not be sorted.',
        ];
        // a=x&b= will transform to a=x&b= (always keep equal sign)
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":"a=1337&b=","hash_fragment":null}',
            'https://example.com/?a=1337&b=',
            'Query parameters without value should be sorted normally.',
        ];
        // a=x&b will transform to a=x&b=
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":"a=1337&b=","hash_fragment":null}',
            'https://example.com/?a=1337&b',
            'Boolean Query parameters (?b instead of ?b=) should be handled properly. ',
        ];
        // a=1&b&a=2 will transform to a=1&a=2&b
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":"a=1337&a=42&b=x","hash_fragment":null}',
            'https://example.com/?a=1337&b=x&a=42',
            'Expecting that no',
        ];
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":"a=1337&a=42&b=x","hash_fragment":null}',
            'https://example.com/?a=42&b=x&a=1337',
            'Multiple occurences of query keys should be sorted properly.',
        ];
        // sorting is guaranteed with array keys
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":"a[]=b&a[]=x","hash_fragment":null}',
            'https://example.com/?a[]=x&a[]=b',
            'Array keys in query string should be sorted properly.',
        ];
        // Keep order with empty values
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":"a=&z=","hash_fragment":null}',
            'https://example.com/?z&a',
            'Array keys wihout values should be sorted properly.',
        ];
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":"a=-1&a=1","hash_fragment":null}',
            'https://example.com/?a=1&a=-1',
            'Integer values should be handled properly',
        ];
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":null,"hash_fragment":null}',
            'https://example.com/#?foo=bar',
            'Irreglar query strings after hash sign must be ignored',
        ];
        yield [
            '{"hash_scheme":"https","hash_userinfo":null,"hash_host":"example.com","hash_port":null,"hash_path":"/","hash_query":null,"hash_fragment":null}',
            'https://example.com/?#?foo=bar',
            'Irreglar query strings after hash sign must be ignored',
        ];
    }


    public function getHashOptionsAndExpectedUrls(): iterable
    {
        $url = 'https://user:hunter2@subdomain.example.com:42/path/to?zfoo=bar&qux=baz#anchor';

        $urls = [
            [
                'opts'     => [
                    'hash_scheme'   => true,
                    'hash_userinfo' => false,
                    'hash_host'     => false,
                    'hash_port'     => false,
                    'hash_path'     => false,
                    'hash_query'    => false,
                    'hash_fragment' => false,
                ],
                'expected' => '{"hash_scheme":"https","hash_userinfo":null,"hash_host":null,"hash_port":null,"hash_path":"","hash_query":null,"hash_fragment":null}',
            ],
            [
                'opts'     => [
                    'hash_scheme'   => true,
                    'hash_userinfo' => true,
                    'hash_host'     => false,
                    'hash_port'     => false,
                    'hash_path'     => false,
                    'hash_query'    => false,
                    'hash_fragment' => false,
                ],
                'expected' => '{"hash_scheme":"https","hash_userinfo":"user:hunter2","hash_host":null,"hash_port":null,"hash_path":"","hash_query":null,"hash_fragment":null}',
            ],
            [
                'opts'     => [
                    'hash_scheme'   => true,
                    'hash_userinfo' => true,
                    'hash_host'     => true,
                    'hash_port'     => false,
                    'hash_path'     => false,
                    'hash_query'    => false,
                    'hash_fragment' => false,
                ],
                'expected' => '{"hash_scheme":"https","hash_userinfo":"user:hunter2","hash_host":"subdomain.example.com","hash_port":null,"hash_path":"","hash_query":null,"hash_fragment":null}',
            ],
            [
                'opts'     => [
                    'hash_scheme'   => true,
                    'hash_userinfo' => true,
                    'hash_host'     => true,
                    'hash_port'     => true,
                    'hash_path'     => false,
                    'hash_query'    => false,
                    'hash_fragment' => false,
                ],
                'expected' => '{"hash_scheme":"https","hash_userinfo":"user:hunter2","hash_host":"subdomain.example.com","hash_port":42,"hash_path":"","hash_query":null,"hash_fragment":null}',
            ],
            [
                'opts'     => [
                    'hash_scheme'   => true,
                    'hash_userinfo' => true,
                    'hash_host'     => true,
                    'hash_port'     => true,
                    'hash_path'     => true,
                    'hash_query'    => false,
                    'hash_fragment' => false,
                ],
                'expected' => '{"hash_scheme":"https","hash_userinfo":"user:hunter2","hash_host":"subdomain.example.com","hash_port":42,"hash_path":"/path/to","hash_query":null,"hash_fragment":null}',
            ],
            [
                'opts'     => [
                    'hash_scheme'   => true,
                    'hash_userinfo' => true,
                    'hash_host'     => true,
                    'hash_port'     => true,
                    'hash_path'     => true,
                    'hash_query'    => true,
                    'hash_fragment' => false,
                ],
                'expected' => '{"hash_scheme":"https","hash_userinfo":"user:hunter2","hash_host":"subdomain.example.com","hash_port":42,"hash_path":"/path/to","hash_query":"qux=baz&zfoo=bar","hash_fragment":null}',
            ],
            [
                'opts'     => [
                    'hash_scheme'   => false,
                    'hash_userinfo' => true,
                    'hash_host'     => true,
                    'hash_port'     => true,
                    'hash_path'     => true,
                    'hash_query'    => true,
                    'hash_fragment' => true,
                ],
                'expected' => '{"hash_scheme":null,"hash_userinfo":"user:hunter2","hash_host":"subdomain.example.com","hash_port":42,"hash_path":"/path/to","hash_query":"qux=baz&zfoo=bar","hash_fragment":"anchor"}',
            ],
        ];

        foreach ($urls as $urlData) {
            yield [$url, $urlData['opts'], $urlData['expected']];
        }
    }
}