<?php

namespace UrlSignature;

use League\Uri\Parser\QueryString as LeagueQueryString;

/**
 * Class QueryString
 *
 * This class acts as a facade to League\Uri\Parser\QueryString to simplify the annoying conversion of array parts.
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

    public static function getKeyValuePairs(?string $query): array
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

        // Sort the keys alphabetically to ensure that the same order is always maintained - this is necessary so that
        // the hash is identical even if the order from the query string is different.
        ksort($keyValuePairs);

        return $keyValuePairs;
    }

    public static function build(array $keyValuePairs): string
    {

        // Sort the keys alphabetically to ensure that the same order is always maintained - this is necessary so that
        // the hash is identical even if the order from the query string is different.
        ksort($keyValuePairs);

        $pairs = [];
        foreach ($keyValuePairs as $key => $value) {
            $pairs[] = [$key, $value];
        }

        return (string) LeagueQueryString::build($pairs);
    }

    public static function append(?string $query, string $key, string $value): ?string
    {

        if(empty($query)) {
            return static::build([$key => $value]);
        }

        $pairs = LeagueQueryString::parse($query);
        $pairs[] = [$key, $value];
        return LeagueQueryString::build($pairs);
    }
}
