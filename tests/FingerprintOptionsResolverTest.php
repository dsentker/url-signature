<?php

namespace UrlFingerprintTest;

use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use UrlFingerprint\FingerprintOptionsResolver;

class FingerprintOptionsResolverTest extends TestCase
{
    public function testExceptionIsThrownWhenSecretIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        (new FingerprintOptionsResolver())->resolve([]);
    }

    /**
     * @dataProvider getInvalidSecretValues
     */
    public function testExceptionIsThrownWhenSecretHasInvalidType($value)
    {
        $this->expectException(InvalidOptionsException::class);
        (new FingerprintOptionsResolver())->resolve([
            'secret' => $value,
        ]);
    }

    public function testExceptionIsThrownWhenSecretIsEmptyString()
    {
        $this->expectException(InvalidOptionsException::class);
        (new FingerprintOptionsResolver())->resolve([
            'secret' => '',
        ]);
    }

    public function testExceptionIsThrownWhenHashAlgoIsNotSupported()
    {
        $this->expectException(InvalidOptionsException::class);
        (new FingerprintOptionsResolver())->resolve([
            'secret'    => '42',
            'hash_algo' => 'thisIsNotAHashAlgorithm',
        ]);
    }

    public function testExceptionIsThrownWhenInvalidOptionPassed()
    {
        $this->expectException(UndefinedOptionsException::class);
        (new FingerprintOptionsResolver())->resolve([
            'secret'        => '42',
            'not_an_option' => 1337,
        ]);
    }

    /**
     * @dataProvider getBooleanOptions
     */
    public function testHashFlagsMustBeBoolean(string $booleanOptionKey)
    {
        $invalidValues = [null, 'true', '', new stdClass()];
        foreach ($invalidValues as $invalidValue) {
            $this->expectException(InvalidOptionsException::class);
            (new FingerprintOptionsResolver())->resolve([
                'secret'          => '42',
                $booleanOptionKey => $invalidValue,
            ]);
        }
    }

    public function getInvalidSecretValues(): iterable
    {
        yield [null];
        yield [42];
        yield [new stdClass()];
    }

    public function getInvalidBooleanValues(): iterable
    {
        yield [null];
        yield ['true'];
        yield [''];
        yield [new stdClass()];
    }

    public function getBooleanOptions(): iterable
    {
        return [
            ['hash_scheme'],
            ['hash_userinfo'],
            ['hash_host'],
            ['hash_port'],
            ['hash_path'],
            ['hash_query'],
            ['hash_fragment'],
        ];
    }
}
