<?php

namespace UrlSignature;

use function League\Uri\build;

abstract class SignatureGenerator
{

    /** @var string */
    public $lastHashedUrl;

    abstract public function getConfiguration(): HashConfiguration;

    /**
     * @param array $urlComponents
     *
     * @return string lowercase hexits as hash from hash_hmac
     */
    protected function getUrlSignature(array $urlComponents): string
    {
        $hashData = build($this->getHashDataFromUri($urlComponents));
        $configuration = $this->getConfiguration();

        return hash_hmac($configuration->getAlgorithm(), $hashData, $configuration->getKey());
    }

    /**
     * Check if a single url component is required for hash value and returns the
     * hashable URL components as array.
     *
     * @param string[] $urlParts
     *
     * @return array<string, string>
     */
    private function getHashDataFromUri(array $urlParts): array
    {

        $hashParts = [];
        $configuration = $this->getConfiguration();

        if ($configuration->hasHashConfigFlag(HashConfiguration::FLAG_HASH_SCHEME)) {
            $hashParts['scheme'] = $urlParts['scheme'];
        }
        if ($configuration->hasHashConfigFlag(HashConfiguration::FLAG_HASH_HOST)) {
            $hashParts['host'] = $urlParts['host'];
        }
        if ($configuration->hasHashConfigFlag(HashConfiguration::FLAG_HASH_PORT)) {
            $hashParts['port'] = $urlParts['port'];
        }
        if ($configuration->hasHashConfigFlag(HashConfiguration::FLAG_HASH_PATH)) {
            $hashParts['path'] = $urlParts['path'];
        }
        if ($configuration->hasHashConfigFlag(HashConfiguration::FLAG_HASH_QUERY)) {
            $hashParts['query'] = $urlParts['query'];
        }
        if ($configuration->hasHashConfigFlag(HashConfiguration::FLAG_HASH_FRAGMENT)) {
            $hashParts['fragment'] = $urlParts['fragment'];
        }

        return $hashParts;
    }
}
