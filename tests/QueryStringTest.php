<?php

namespace UrlSignatureTest;

use UrlSignature\QueryString;
use PHPUnit\Framework\TestCase;

class QueryStringTest extends TestCase
{

    public function testGetKeyValuePairs()
    {

        $queryString = QueryString::build([
            'foo'  => 'bar',
            'qux'  => '42',
            'test' => null
        ]);

        $this->assertEquals('foo=bar&qux=42&test', $queryString);

    }

    public function testBuildFromString()
    {

        $queryString = 'foo=bar&qux=42&test';
        $parts = QueryString::getKeyValuePairs($queryString);
        $this->assertEquals([
            'foo'  => 'bar',
            'qux'  => '42',
            'test' => null,
        ], $parts);

    }

    public function testAppendOnEmptyString()
    {
        $queryString = '';
        $result = QueryString::append($queryString, 'foo', 'bar');
        $this->assertEquals('foo=bar', $result);
    }

    public function testAppendOnExistingString()
    {
        $queryString = 'qux=test&baz=42';
        $result = QueryString::append($queryString, 'foo', 'bar');
        $this->assertEquals('qux=test&baz=42&foo=bar', $result);
    }
}
