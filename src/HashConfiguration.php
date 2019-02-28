<?php

namespace UrlSignature;

use UrlSignature\Exception\ConfigurationException;

class HashConfiguration
{

    const DEFAULT_SIGNATURE_KEY = '_signature';

    const DEFAULT_TIMEOUT_KEY = '_expires';

    const FLAG_HASH_SCHEME = 2;     // scheme
    const FLAG_HASH_HOST = 4;       // host
    const FLAG_HASH_PORT = 8;       // port
    const FLAG_HASH_PATH = 16;      // path
    const FLAG_HASH_QUERY = 32;     // query
    const FLAG_HASH_FRAGMENT = 64;  // fragment


    /** @var string */
    private $key;

    /** @var string */
    private $signatureUrlKey;

    /** @var string */
    private $timeoutUrlKey;

    /** @var int */
    private $hashMask = self::FLAG_HASH_HOST | self::FLAG_HASH_PATH | self::FLAG_HASH_QUERY;

    /**
     * Current hashing algorithm
     *
     * @var string
     */
    protected $algorithm = 'SHA256';

    /**
     * Builder constructor.
     *
     * @param string $key
     * @param string $signatureUrlKey
     * @param string $timeoutUrlKey
     */
    public function __construct(string $key, string $signatureUrlKey = self::DEFAULT_SIGNATURE_KEY, string $timeoutUrlKey = self::DEFAULT_TIMEOUT_KEY)
    {

        if ($signatureUrlKey === $timeoutUrlKey) {
            throw ConfigurationException::differentKeysRequired($signatureUrlKey);
        }

        $this->key = $key;
        $this->signatureUrlKey = $signatureUrlKey;
        $this->timeoutUrlKey = $timeoutUrlKey;
    }

    /**
     * Shortcut for config creation with default values.
     *
     * @param string $key
     *
     * @return HashConfiguration
     */
    public static function create(string $key)
    {
        return new static($key);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getSignatureUrlKey(): string
    {
        return $this->signatureUrlKey;
    }

    /**
     * @param string $signatureUrlKey
     */
    public function setSignatureUrlKey(string $signatureUrlKey)
    {
        $this->signatureUrlKey = $signatureUrlKey;
    }

    /**
     * @return string
     */
    public function getTimeoutUrlKey(): string
    {
        return $this->timeoutUrlKey;
    }

    /**
     * @param string $timeoutUrlKey
     */
    public function setTimeoutUrlKey(string $timeoutUrlKey)
    {
        $this->timeoutUrlKey = $timeoutUrlKey;
    }

    /**
     * @return int
     */
    public function getHashMask(): int
    {
        return $this->hashMask;
    }

    /**
     * @param int|int[] $hashMask
     */
    public function setHashMask(...$hashMask)
    {
        /*
         * If multiple arguments are used, the values get combined with a bitwise conjunction with
         * the help of array_reduce.
         * You can understand that better if you interpret the method call HashConfigFactory::setHashMask(2, 4, 8) to
         * the following conjunction: 2 | 4 | 8
         */
        $this->hashMask = array_reduce($hashMask, function($a, $b) {
            return $a | $b;
        });
    }


    /**
     * @param int $configFlag
     *
     * @return bool
     */
    public function hasHashConfigFlag(int $configFlag)
    {
        return (bool)($this->hashMask & $configFlag);
    }

    /**
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * @param string $algorithm
     */
    public function setAlgorithm(string $algorithm): void
    {

        $registeredAlgos = hash_hmac_algos();

        // TODO Are there platforms where the algorithms are capitalized?
        if (!in_array(strtolower($algorithm), $registeredAlgos, true)) {
            throw ConfigurationException::invalidAlgorithm($algorithm, $registeredAlgos);
        }

        $this->algorithm = $algorithm;
    }

}