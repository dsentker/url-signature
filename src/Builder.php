<?php

namespace UrlSignature;

use DateTimeInterface;
use UrlSignature\Exception\TimeoutException;
use function League\Uri\parse;
use function League\Uri\build;

class Builder extends SignatureGenerator
{

    /** @var HashConfiguration */
    private $config;

    public function __construct(HashConfiguration $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $url     The URL to sign
     * @param mixed  $timeout A timestamp, date or string which sets the lifetime for the hash.
     *
     * @return string The complete URL with the generated hash in query
     * @throws TimeoutException
     */
    public function signUrl(string $url, $timeout = null): string
    {

        $urlComponents = parse($url);

        // Check if url has already signature and/or timeout
        $queryParts = QueryString::getKeyValuePairs($urlComponents['query']);
        if (array_key_exists($this->config->getSignatureUrlKey(), $queryParts)) {
            unset($queryParts[$this->config->getSignatureUrlKey()]);
        }
        if (array_key_exists($this->config->getTimeoutUrlKey(), $queryParts)) {
            unset($queryParts[$this->config->getTimeoutUrlKey()]);
        }

        // Add timeout to query string, if given
        if ($timeout !== null) {
            $timeoutTimestamp = $this->timeoutToInt($timeout);
            if ($timeoutTimestamp < time()) {
                throw TimeoutException::notValid('Timeout cannot be in the past');
            }
            $queryParts[$this->config->getTimeoutUrlKey()] = $timeoutTimestamp;
        }

        $urlComponents['query'] = QueryString::build($queryParts);

        // Add generated hash value to query string.
        $signature = $this->getUrlSignature($urlComponents);
        $urlComponents['query'] = QueryString::append($urlComponents['query'], $this->config->getSignatureUrlKey(), $signature);


        return build($urlComponents);

    }

    /**
     * Returns a timestamp (as integer) from an unknown $timeout type.
     *
     * @param mixed $timeout
     *
     * @return int The timestamp for the timeout
     * @throws TimeoutException
     */
    private function timeoutToInt($timeout): int
    {
        if (is_int($timeout)) {
            $timeoutTimestamp = $timeout;
        } elseif (is_string($timeout)) {
            $timeoutTimestamp = strtotime($timeout);
            if (false === $timeoutTimestamp) {
                throw TimeoutException::notParsable();
            }
        } elseif ($timeout instanceof DateTimeInterface) {
            $timeoutTimestamp = $timeout->getTimestamp();
        } else {
            throw TimeoutException::unknownFormat($timeout);
        }

        return $timeoutTimestamp;
    }

    public function getConfiguration(): HashConfiguration
    {
        return $this->config;
    }

    /**
     * Creates a new validator with the same configuration
     *
     * @return Validator
     */
    public function createValidator(): Validator
    {
        return new Validator($this->getConfiguration());
    }
}
