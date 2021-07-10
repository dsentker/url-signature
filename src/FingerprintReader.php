<?php

namespace UrlFingerprint;

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

    private function serializeUrlParts(array $parts): string
    {
        return json_encode($parts, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function sortQuery(Uri $uri): ?string
    {
        // Sort query string
        $queryArray = [];
        $queryString = null;
        if ($uri->getQuery()) {
            foreach (Query::createFromUri($uri)->pairs() as $key => $value) {
                $value = empty($value) ? '' : $value;
                $queryArray[] = sprintf('%s=%s', $key, $value);
            }
            sort($queryArray);
            $queryString = implode('&', $queryArray);
        }

        return $queryString;
    }

    /**
     * @throws InvalidUrl
     * @throws \JsonException
     */
    public function capture(string $url): Fingerprint
    {

        $url = trim($url);
        if($url === '') {
            throw InvalidUrl::isEmpty();
        }

        try {
            $uri = UriModifier::sortQuery(Uri::createFromString($url));
        } catch(SyntaxError $error) {
            throw InvalidUrl::syntaxError($error);
        }

        if ($uri->getScheme() === null && $this->options['hash_scheme']) {
            throw InvalidUrl::schemeIsMissing($url);
        }

        $hashedParts = [];

        $urlPartsToCheck = [
            'hash_scheme'   => fn(Uri $uri) => $uri->getScheme(),
            'hash_userinfo' => fn(Uri $uri) => $uri->getUserInfo(),
            'hash_host'     => fn(Uri $uri) => $uri->getHost(),
            'hash_port'     => fn(Uri $uri) => $uri->getPort(),
            'hash_path'     => fn(Uri $uri) => $uri->getPath(),
            'hash_query'    => fn(Uri $uri) => $this->sortQuery($uri),
            'hash_fragment' => fn(Uri $uri) => $uri->getFragment(),
        ];

        foreach ($urlPartsToCheck as $option => $cb) {
            $placeHolderValue = 'hash_path' === $option ? '' : null; // hash_path is never null!
            $hashedParts[$option] = ($this->options[$option])
                ? $cb($uri)
                : $placeHolderValue;
        }

        $gist = $this->serializeUrlParts($hashedParts);

        $hash = $this->getHash($gist);

        if (null === $hash) {
            throw InvalidHashAlgorithm::hashUnknown($this->options['algo']);
        }

        return new Fingerprint($gist, $this->options['hash_algo'], $hash);
    }

    public function compare(Fingerprint $known, Fingerprint $fingerprint): bool
    {
        return hash_equals(
            $this->getHash($known->getGist()),
            $this->getHash($fingerprint->getGist())
        );
    }

    private function getHash(string $string): ?string {
        return hash_hmac(
            $this->options['hash_algo'],
            $string,
            $this->options['secret']
        ) ?: null;
    }
}
