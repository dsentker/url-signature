<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 22.02.2019
 * Time: 12:35
 */
namespace HashedUri;

use HashedUri\Exception\SignatureExpiredException;
use HashedUri\Exception\SignatureInvalidException;
use HashedUri\Exception\SignatureNotFoundException;
use HashedUri\Exception\ValidationException;
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
            $this->verify($url);
        } catch(ValidationException $e) {
            return false;
        }

        return true;
    }

    public function verify(string $url)
    {

        $urlComponents = parse($url);
        $query = $urlComponents['query'];
        $queryParts = QueryString::getKeyValuePairs($query);

        if(!array_key_exists($this->config->getSignatureUrlKey(), $queryParts)) {
            throw SignatureNotFoundException::notPresetInQueryString($query);
        }

        // Validate timeout
        if(array_key_exists($this->config->getTimeoutUrlKey(), $queryParts)) {
            $urlTimeout = $queryParts[$this->config->getTimeoutUrlKey()];
            if($urlTimeout < time()) {
                throw SignatureExpiredException::timeoutViolation();
            }
        }

        $signatureHash = (string) $queryParts[$this->config->getSignatureUrlKey()]; // Cast to string in case of NULL
        if(empty($signatureHash)) {
            throw SignatureInvalidException::emptySignature($signatureHash);
        }

        // Remove signature from query part to make sure that it is not used for the hash
        unset($queryParts[$this->config->getSignatureUrlKey()]);

        $actualHash = $this->getUrlSignature($urlComponents);

        return hash_equals($signatureHash, $actualHash);
    }

    public function getConfiguration(): HashConfiguration
    {
        return $this->config;
    }


}