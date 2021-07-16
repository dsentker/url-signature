<?php

namespace UrlFingerprint;

final class Fingerprint
{
    private string $gist;

    private string $hashAlgo;

    private string $digest;

    public function __construct(string $gist, string $hashAlgo, string $digest)
    {
        $this->gist = $gist;
        $this->hashAlgo = $hashAlgo;
        $this->digest = $digest;
    }

    public function getGist(): string
    {
        return $this->gist;
    }

    public function getHashAlgo(): string
    {
        return $this->hashAlgo;
    }

    public function getDigest(): string
    {
        return $this->digest;
    }

    public function __toString(): string
    {
        return $this->digest;
    }
}
