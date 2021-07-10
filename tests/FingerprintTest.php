<?php

namespace UrlFingerprintTest;

use PHPUnit\Framework\TestCase;
use UrlFingerprint\Fingerprint;

class FingerprintTest extends TestCase
{
    public function testGetter()
    {
        $fingerprint = new Fingerprint('{"hash_scheme":"https","hash_userinfo":null,"hash_host":"www.example.com","hash_port":null,"hash_path":"/","hash_query":null,"hash_fragment":"anchor"}',
            'md5', '1337');

        $this->assertEquals('{"hash_scheme":"https","hash_userinfo":null,"hash_host":"www.example.com","hash_port":null,"hash_path":"/","hash_query":null,"hash_fragment":"anchor"}', $fingerprint->getGist());
        $this->assertEquals('md5', $fingerprint->getHashAlgo());
        $this->assertEquals('1337', $fingerprint->getHash());
        $this->assertEquals('1337', (string)$fingerprint);

    }
}