<?php

namespace UrlSignature;

use UrlSignature\Exception\SignatureExpiredException;
use UrlSignature\Exception\SignatureInvalidException;
use UrlSignature\Exception\SignatureNotFoundException;
use UrlSignature\Exception\ValidationException;
use function League\Uri\parse;

class Validator extends SignatureGenerator
{

    /** @var HashConfiguration */
    private $config;

    /**
     * Builder constructor.
     *
     * @param HashConfiguration $config
     */
    public function __construct(HashConfiguration $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public function isValid(string $url): bool
    {
        try {
            $isValid = $this->verify($url);
        } catch (ValidationException $e) {
            return false;
        }

        return $isValid;
    }

    /**
     * @param string $url
     *
     * @return bool
     *
     * @throws SignatureExpiredException
     * @throws SignatureInvalidException
     * @throws SignatureNotFoundException
     */
    public function verify(string $url)
    {
        $urlComponents = parse($url);
        $query = $urlComponents['query'];
        $queryParts = QueryString::getKeyValuePairs($query);

        if (!array_key_exists($this->config->getSignatureUrlKey(), $queryParts)) {
            throw SignatureNotFoundException::notPresetInQueryString($query);
        }

        // Validate timeout
        if (array_key_exists($this->config->getTimeoutUrlKey(), $queryParts)) {
            $urlTimeout = $queryParts[$this->config->getTimeoutUrlKey()];
            if ($urlTimeout < time()) {
                throw SignatureExpiredException::timeoutViolation();
            }
        }

        $signatureHash = (string)$queryParts[$this->config->getSignatureUrlKey()]; // Cast to string in case of NULL
        if (empty($signatureHash)) {
            throw SignatureInvalidException::emptySignature($signatureHash);
        }

        // Remove signature from query part to make sure that it is not used for the hash. Reunite to $urlComponents.
        unset($queryParts[$this->config->getSignatureUrlKey()]);
        $urlComponents['query'] = QueryString::build($queryParts);

        $actualHash = $this->getUrlSignature($urlComponents);

        if(false === hash_equals($signatureHash, $actualHash)) {
            throw SignatureInvalidException::signatureDoesNotMatch($actualHash);
        }

        return true;
    }

    public function getConfiguration(): HashConfiguration
    {
        return $this->config;
    }


}