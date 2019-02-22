<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 22.02.2019
 * Time: 15:48
 */

namespace HashedUriTest;

use HashedUri\QueryString;
use PHPUnit\Framework\TestCase;

class QueryStringTest extends TestCase
{

    public function testGetKeyValuePairs()
    {

        $queryString = QueryString::build([
            'foo' => 'bar',
            'qux' => '42',
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
}
