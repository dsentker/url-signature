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
            'secret'          => '42',
            'ignore_port'     => true,
            'ignore_fragment' => true,
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
            'secret'          => '42',
            'ignore_fragment' => false,
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
            'secret'        => '42',
            'hash_algo'     => 'md5',
            'ignore_scheme' => false,
        ]);
        $readerEnforcingScheme->capture('//www.example.com');
    }

    public function testMissingScheme()
    {
        $reader = new FingerprintReader([
            'secret'        => '42',
            'hash_algo'     => 'md5',
            'ignore_scheme' => true,
        ]);
        $fingerprint = $reader->capture('//www.example.com');

        $this->assertEquals(
            '{"scheme":null,"userinfo":null,"host":"www.example.com","port":null,"path":"","query":null,"fragment":null}',
            $fingerprint->gist
        );
    }

    public function testEmptyUri()
    {
        $this->expectException(InvalidUrl::class);
        $this->expectExceptionMessage('The URL string is empty!');
        $fingerprintReader = new FingerprintReader([
            'secret'        => '42',
            'hash_algo'     => 'md5',
            'ignore_scheme' => false,
        ]);
        $fingerprintReader->capture('');
    }

    public function testEmptyCharactersSurroundingUrlWillNotAffectTheResult()
    {
        $reader = new FingerprintReader([
            'secret'          => '42',
            'hash_algo'       => 'md5',
            'ignore_scheme'   => false,
            'ignore_fragment' => false,
        ]);
        $urlHash = $reader->capture(' https://www.example.com/#anchor ');

        $this->assertEquals(
            '{"scheme":"https","userinfo":null,"host":"www.example.com","port":null,"path":"/","query":null,"fragment":"anchor"}',
            $urlHash->gist
        );
    }

    public function testUrlWithSyntaxError()
    {
        $this->expectException(InvalidUrl::class);
        $this->expectExceptionMessage('The uri `https://` is invalid for the `https` scheme.');
        $reader = new FingerprintReader([
            'secret'        => '42',
            'hash_algo'     => 'md5',
            'ignore_scheme' => false,
        ]);
        $reader->capture('https://');
    }

    public function testUrlPathWithWhitespace()
    {
        $reader = new FingerprintReader([
            'secret'        => '42',
            'hash_algo'     => 'md5',
            'ignore_scheme' => true,
        ]);
        $urlHash = $reader->capture('//example.com/foo bar/baz');
        $this->assertEquals(
            '{"scheme":null,"userinfo":null,"host":"example.com","port":null,"path":"/foo%20bar/baz","query":null,"fragment":null}',
            $urlHash->gist
        );
    }

    public function testCompareFingerprints()
    {
        $reader = new FingerprintReader([
            'secret'          => '42',
            'hash_algo'       => 'md5',
            'ignore_scheme'   => true,
            'ignore_query'    => true,
            'ignore_fragment' => true,
        ]);
        $fingerprint1 = $reader->capture('http://www.example.com/foo/bar/?qux=baz');
        $fingerprint2 = $reader->capture('https://www.example.com/foo/bar/#anchor');

        $this->assertTrue(
            $reader->compare($fingerprint1, $fingerprint2),
            sprintf('Assert that gist %s%s (%s) equals gist %s%s (%s).',
                PHP_EOL,
                $fingerprint1->gist,
                $fingerprint1->digest,
                PHP_EOL,
                $fingerprint2->gist,
                $fingerprint2->digest
            )
        );
    }

    public function testCompareFingerprintsShouldPassWithDifferentQueryParameterOrder()
    {
        $reader = new FingerprintReader([
            'secret'       => '42',
            'hash_algo'    => 'md5',
            'ignore_query' => false,
        ]);
        $urlHash1 = $reader->capture('https://www.example.com/foo/?ananas=baz&banana=qux&citrus');
        $urlHash2 = $reader->capture('https://www.example.com/foo/?citrus&banana=qux&ananas=baz');

        $this->assertTrue(
            $reader->compare($urlHash1, $urlHash2),
            sprintf('Assert that gist %s%s (%s) equals gist %s%s (%s).',
                PHP_EOL,
                $urlHash1->gist,
                $urlHash1->digest,
                PHP_EOL,
                $urlHash2->gist,
                $urlHash2->digest
            )
        );
    }

    public function testCompareFingerprintsShouldFailWithDifferentQueryValues()
    {
        $reader = new FingerprintReader([
            'secret'    => '42',
            'hash_algo' => 'md5',
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
                'https://www.example.com/path?foo&baz',
                ['baz', true, 42, null,],
            ],
        ];
    }

    public function getUrlsWithQueryToSort(): iterable
    {
        yield [
            '{"scheme":"http","userinfo":null,"host":"example.com","port":null,"path":"","query":null,"fragment":null}',
            'http://example.com',
            'A URL without query string must not be sorted',
        ];
        yield [
            '{"scheme":"http","userinfo":null,"host":"example.com","port":null,"path":"","query":null,"fragment":null}',
            'http://example.com?',
            'A question mark in URL (no slash) without query string should have no effect',
        ];
        yield [
            '{"scheme":"http","userinfo":null,"host":"example.com","port":null,"path":"/","query":null,"fragment":null}',
            'http://example.com/?',
            'A question mark in URL without query string should have no effect',
        ];
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":"foo=bar","fragment":null}',
            'https://example.com/?foo=bar',
            'A URL with a single query key-value pair should not be sorted',
        ];
        // Sort is done properly / b=x&a=x will transform to a=x&b=x
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":"a=1337&b=42","fragment":null}',
            'https://example.com/?b=42&a=1337',
            'The query string keys should be sorted ASC.',
        ];
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":"a=1337&b=42","fragment":null}',
            'https://example.com/?a=1337&b=42',
            'Query string keys already sorted should not be sorted.',
        ];
        // a=x&b= will transform to a=x&b= (always keep equal sign)
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":"a=1337&b=","fragment":null}',
            'https://example.com/?a=1337&b=',
            'Query parameters without value should be sorted normally.',
        ];
        // a=x&b will transform to a=x&b=
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":"a=1337&b=","fragment":null}',
            'https://example.com/?a=1337&b',
            'Boolean Query parameters (?b instead of ?b=) should be handled properly. ',
        ];
        // a=1&b&a=2 will transform to a=1&a=2&b
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":"a=1337&a=42&b=x","fragment":null}',
            'https://example.com/?a=1337&b=x&a=42',
            'Expecting that no',
        ];
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":"a=1337&a=42&b=x","fragment":null}',
            'https://example.com/?a=42&b=x&a=1337',
            'Multiple occurences of query keys should be sorted properly.',
        ];
        // sorting is guaranteed with array keys
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":"a[]=b&a[]=x","fragment":null}',
            'https://example.com/?a[]=x&a[]=b',
            'Array keys in query string should be sorted properly.',
        ];
        // Keep order with empty values
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":"a=&z=","fragment":null}',
            'https://example.com/?z&a',
            'Array keys wihout values should be sorted properly.',
        ];
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":"a=-1&a=1","fragment":null}',
            'https://example.com/?a=1&a=-1',
            'Integer values should be handled properly',
        ];
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":null,"fragment":null}',
            'https://example.com/#?foo=bar',
            'Irreglar query strings after hash sign must be ignored',
        ];
        yield [
            '{"scheme":"https","userinfo":null,"host":"example.com","port":null,"path":"/","query":null,"fragment":null}',
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
                    'ignore_scheme'   => false,
                    'ignore_userinfo' => true,
                    'ignore_host'     => true,
                    'ignore_port'     => true,
                    'ignore_path'     => true,
                    'ignore_query'    => true,
                    'ignore_fragment' => true,
                ],
                'expected' => '{"scheme":"https","userinfo":null,"host":null,"port":null,"path":"","query":null,"fragment":null}',
            ],
            [
                'opts'     => [
                    'ignore_scheme'   => false,
                    'ignore_userinfo' => false,
                    'ignore_host'     => true,
                    'ignore_port'     => true,
                    'ignore_path'     => true,
                    'ignore_query'    => true,
                    'ignore_fragment' => true,
                ],
                'expected' => '{"scheme":"https","userinfo":"user:hunter2","host":null,"port":null,"path":"","query":null,"fragment":null}',
            ],
            [
                'opts'     => [
                    'ignore_scheme'   => false,
                    'ignore_userinfo' => false,
                    'ignore_host'     => false,
                    'ignore_port'     => true,
                    'ignore_path'     => true,
                    'ignore_query'    => true,
                    'ignore_fragment' => true,
                ],
                'expected' => '{"scheme":"https","userinfo":"user:hunter2","host":"subdomain.example.com","port":null,"path":"","query":null,"fragment":null}',
            ],
            [
                'opts'     => [
                    'ignore_scheme'   => false,
                    'ignore_userinfo' => false,
                    'ignore_host'     => false,
                    'ignore_port'     => false,
                    'ignore_path'     => true,
                    'ignore_query'    => true,
                    'ignore_fragment' => true,
                ],
                'expected' => '{"scheme":"https","userinfo":"user:hunter2","host":"subdomain.example.com","port":42,"path":"","query":null,"fragment":null}',
            ],
            [
                'opts'     => [
                    'ignore_scheme'   => false,
                    'ignore_userinfo' => false,
                    'ignore_host'     => false,
                    'ignore_port'     => false,
                    'ignore_path'     => false,
                    'ignore_query'    => true,
                    'ignore_fragment' => true,
                ],
                'expected' => '{"scheme":"https","userinfo":"user:hunter2","host":"subdomain.example.com","port":42,"path":"/path/to","query":null,"fragment":null}',
            ],
            [
                'opts'     => [
                    'ignore_scheme'   => false,
                    'ignore_userinfo' => false,
                    'ignore_host'     => false,
                    'ignore_port'     => false,
                    'ignore_path'     => false,
                    'ignore_query'    => false,
                    'ignore_fragment' => true,
                ],
                'expected' => '{"scheme":"https","userinfo":"user:hunter2","host":"subdomain.example.com","port":42,"path":"/path/to","query":"qux=baz&zfoo=bar","fragment":null}',
            ],
            [
                'opts'     => [
                    'ignore_scheme'   => true,
                    'ignore_userinfo' => false,
                    'ignore_host'     => false,
                    'ignore_port'     => false,
                    'ignore_path'     => false,
                    'ignore_query'    => false,
                    'ignore_fragment' => false,
                ],
                'expected' => '{"scheme":null,"userinfo":"user:hunter2","host":"subdomain.example.com","port":42,"path":"/path/to","query":"qux=baz&zfoo=bar","fragment":"anchor"}',
            ],
        ];

        foreach ($urls as $urlData) {
            yield [$url, $urlData['opts'], $urlData['expected']];
        }
    }
}