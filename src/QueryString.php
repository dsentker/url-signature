<?php

namespace UrlSignature;

use League\Uri\Parser\QueryString as LeagueQueryString;

/**
 * This class acts as a facade to League\Uri\Parser\QueryString to simplify the string-array-conversion of query string
 * parts. It fulfills two main issues. The first issue is that the query string parameters can be in mixed order, so the
 * generated hash value will not match. This facade maintains the ordering of all parameters to guarantee equal hashes.
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
     * @param string|null $query
     *
     * @return array<string, string>
     */
    public static function getKeyValuePairs(?string $query): array
    {

        if (empty($query)) {
            return [];
        }

        $keyValuePairs = [];
        $pairs = LeagueQueryString::parse($query);
        foreach ($pairs as $pair) {
            [$key, $value] = $pair;
            $keyValuePairs[$key] = $value;
        }

        // Sort the keys alphabetically to ensure that the same order is always maintained - this is necessary so that
        // the hash is identical even if the order from the query string is different.
        ksort($keyValuePairs);

        return $keyValuePairs;
    }

    /**
     * Sort the keys alphabetically to ensure that the same order is always maintained - this is necessary so that
     * the hash is identical even if the order from the query string is different.
     * @param array $keyValuePairs
     * @return array
     */
    public static function normalizeKeyValuePairs(array $keyValuePairs): array
    {
        ksort($keyValuePairs);

        $pairs = [];
        foreach ($keyValuePairs as $key => $value) {
            $pairs[] = [$key, $value];
        }

        return $pairs;
    }

    public static function normalizeQueryString(string $queryString): string
    {
        return LeagueQueryString::build(
            self::normalizeKeyValuePairs(
                self::getKeyValuePairs($queryString)
            )
        );
    }

    /**
     * Create a query string based on key-value pairs. The $keyValuePairs will be normalized.
     * @param array<string, string> $keyValuePairs
     */
    public static function build(array $keyValuePairs): string
    {

        return (string)LeagueQueryString::build(self::normalizeKeyValuePairs($keyValuePairs));
    }

    public static function append(?string $query, string $key, string $value): ?string
    {

        if (empty($query)) {
            return static::build([$key => $value]);
        }

        $pairs = LeagueQueryString::parse($query);
        $pairs[] = [$key, $value];
        return LeagueQueryString::build($pairs);
    }
}
