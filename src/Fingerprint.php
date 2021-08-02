<?php

namespace UrlFingerprint;

/**
 * A DAO representing the digest / hash result including hash input data.
 * TODO Use constructor promotion in PHP 8.0
 * TODO Use readonly properties in PHP 8.1
 */
final class Fingerprint
{
    public string $gist;

    public string $hashAlgo;

    public string $digest;

    public function __construct(string $gist, string $hashAlgo, string $digest)
    {
        $this->gist = $gist;
        $this->hashAlgo = $hashAlgo;
        $this->digest = $digest;
    }

    public function __toString(): string
    {
        return $this->digest;
    }
}
