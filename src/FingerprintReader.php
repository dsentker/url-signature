<?php

namespace UrlFingerprint;

use JsonException;
use League\Uri\Components\Query;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\Uri;
use League\Uri\UriModifier;
use UrlFingerprint\Exception\InvalidHashAlgorithm;
use UrlFingerprint\Exception\InvalidUrl;

final class FingerprintReader
{

    private array $options;

    public function __construct(array $options)
    {
        $this->options = (new FingerprintOptionsResolver())->resolve($options);
    }

    /**
     * @throws JsonException
     */
    private function serializeUrlParts(array $parts): string
    {
        return json_encode($parts, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeQueryString(Uri $uri, array $queryParametersToIgnore = []): ?string
    {
        // Sort query string
        $queryArray = [];
        $queryString = null;
        if ($uri->getQuery()) {
            $pairs = Query::createFromUri($uri)->pairs();
            foreach ($pairs as $key => $value) {
                /*
                 * Check if $key is not ignored. The key may contain array notation like foo[] or bar[2][x]. In this case,
                 * ensure that the native name without array brackets is used for comparison. foo[] become foo
                 * and bar[2][x] become bar.
                 */
                preg_match('#^(\w+)(\[.*])?$#', $key, $matches);
                $normalizedKey = $matches[1] ?? $key;

                if ( ! in_array($normalizedKey, $queryParametersToIgnore, true)) {
                    $value = empty($value) ? '' : $value;
                    $queryArray[] = sprintf('%s=%s', $key, $value);
                }
            }
            sort($queryArray);
            $queryString = implode('&', $queryArray);
        }

        return $queryString ?: null;
    }

    /**
     * @throws InvalidUrl
     * @throws JsonException
     */
    public function capture(string $url, array $queryParametersToIgnore = []): Fingerprint
    {
        $url = trim($url);
        if ($url === '') {
            throw InvalidUrl::isEmpty();
        }

        try {
            $uri = UriModifier::sortQuery(Uri::createFromString($url));
        } catch (SyntaxError $error) {
            throw InvalidUrl::syntaxError($error);
        }

        if ($uri->getScheme() === null && ! $this->options['ignore_scheme']) {
            throw InvalidUrl::schemeIsMissing($url);
        }

        $hashedParts = [];

        $urlPartsToCheck = [
            'ignore_scheme'   => fn(Uri $uri) => $uri->getScheme(),
            'ignore_userinfo' => fn(Uri $uri) => $uri->getUserInfo(),
            'ignore_host'     => fn(Uri $uri) => $uri->getHost(),
            'ignore_port'     => fn(Uri $uri) => $uri->getPort(),
            'ignore_path'     => fn(Uri $uri) => $uri->getPath(),
            'ignore_query'    => fn(Uri $uri) => $this->normalizeQueryString($uri, $queryParametersToIgnore),
            'ignore_fragment' => fn(Uri $uri) => $uri->getFragment(),
        ];

        foreach ($urlPartsToCheck as $option => $cb) {
            $emptyValue = 'ignore_path' === $option ? '' : null; // ignore_path is never null!

            $hashedParts[str_replace('ignore_', '', $option)] = ($this->options[$option])
                ? $emptyValue
                : $cb($uri);
        }

        $gist = $this->serializeUrlParts($hashedParts);

        $digest = $this->createDigest($gist);

        if (null === $digest) {
            throw InvalidHashAlgorithm::unknownAlgorithm($this->options['hash_algo']);
        }

        return new Fingerprint($gist, $this->options['hash_algo'], $digest);
    }

    /**
     * Returns true of both fingerprints are equal
     */
    public function compare(Fingerprint $known, Fingerprint $fingerprint): bool
    {
        return hash_equals(
            $this->createDigest($known->gist),
            $this->createDigest($fingerprint->gist)
        );
    }

    private function createDigest(string $string): string
    {
        $digest = hash_hmac(
            $this->options['hash_algo'],
            $string,
            $this->options['secret']
        );

        if (null === $digest) {
            throw InvalidHashAlgorithm::unknownAlgorithm($this->options['hash_algo']);
        }

        return $digest;
    }
}
