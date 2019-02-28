<?php

foreach([
            '//example.com',
            '//example.com?foo=bar',
            '//example.com/',
            '//example.com/foo',
            '//example.com/?foo',
            '//example.com/foo?bar',
            '//example.com/foo?bar=',
            '//example.com/foo?bar=baz',
            '//example.com/foo?bar=baz&qux=pax',
            '//example.com',
            '//example.com?foo=bar',
            '//example.com/',
            '//example.com/foo',
            '//example.com/?foo',
            '//example.com/foo?bar',
            '//example.com/foo?bar=',
            '//example.com/foo?bar=baz',
            '//example.com/foo?bar=baz&qux=pax',
            '//example.com/foo?bar=baz&qux=pax#fragment',
            '//example.com:81/foo?bar=baz&qux=pax#fragment',
            '//subdomain.example.com:81/foo?bar=baz&qux=pax#fragment',
            'relative/path/to/foo',
            '/foo?bar',
            '/foo/an-unusal-long-uri-with-special%20characters?which&should=be_no_problem',
            '/foo/?bar=qux',
            '/'
        ] as $url) {

    $urlWithoutSignature;

    printf("['%s', '%s'],<br>", $url, hash_hmac('SHA256', $url, 'secure-key'));
}