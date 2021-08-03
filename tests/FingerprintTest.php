<?php

namespace UrlFingerprintTest;

use PHPUnit\Framework\TestCase;
use UrlFingerprint\Fingerprint;

class FingerprintTest extends TestCase
{
    public function testFingerprintIsStringable()
    {
        $fingerprint = new Fingerprint('{"ignore_scheme":"https","ignore_userinfo":null,"ignore_host":"www.example.com","ignore_port":null,"ignore_path":"/","ignore_query":null,"ignore_fragment":"anchor"}',
            'md5', '1337');

        $this->assertEquals('1337', $fingerprint->digest);
        $this->assertEquals('1337', (string)$fingerprint);

    }
}