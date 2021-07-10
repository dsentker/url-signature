<?php

namespace UrlHasher;

class Fingerprint
{
    private string $gist;

    private string $hashAlgo;

    private string $hash;

    public function __construct(string $gist, string $hashAlgo, string $hash)
    {
        $this->gist = $gist;
        $this->hashAlgo = $hashAlgo;
        $this->hash = $hash;
    }

    public function getGist(): string
    {
        return $this->gist;
    }

    public function getHashAlgo(): string
    {
        return $this->hashAlgo;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}