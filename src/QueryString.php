<?php

namespace UrlSignature;

use League\Uri\Parser\QueryString as LeagueQueryString;

/**
 * Class QueryString
 *
 * This class acts as a face to League\Uri\Parser\QueryString to simplify the annoying conversion of array parts.
 * In League Query String, the query parts looks like this:
 * $foo = [
 *      ['key1', 'value1'],
 *      ['key2', 'value2'],
 * ]
 *
 * This class converts the array data to regular key-value pairs like this:
 * $foo = [
 *      'key1' => 'value1',
 *      'key2' => 'value2'
 * ]
 *
 * @package UrlSignature
 */
class QueryString
{

    /**
     * @param string $query
     *
     * @return array
     */
    public static function getKeyValuePairs(?string $query)
    {

        if (empty($query)) {
            return [];
        }

        $keyValuePairs = [];
        $pairs = LeagueQueryString::parse($query);
        foreach ($pairs as $pair) {
            list($key, $value) = $pair;
            $keyValuePairs[$key] = $value;
        }

        return $keyValuePairs;
    }

    /**
     * @param array $keyValuePairs
     *
     * @return string
     */
    public static function build(array $keyValuePairs)
    {
        $pairs = [];
        foreach ($keyValuePairs as $key => $value) {
            $pairs[] = [$key, $value];
        }

        /** @noinspection PhpParamsInspection */
        return LeagueQueryString::build($pairs);
    }


}