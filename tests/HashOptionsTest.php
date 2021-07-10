<?php

namespace UrlHasherTest;

use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use UrlHasher\HashOptionsResolver;

class HashOptionsTest extends TestCase
{
    public function testExceptionIsThrownWhenSecretIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        (new HashOptionsResolver())->resolve([]);
    }

    /**
     * @dataProvider getInvalidSecretValues
     */
    public function testExceptionIsThrownWhenSecretHasInvalidType($value)
    {
        $this->expectException(InvalidOptionsException::class);
        (new HashOptionsResolver())->resolve([
            'secret' => $value,
        ]);
    }

    public function testExceptionIsThrownWhenSecretIsEmptyString()
    {
        $this->expectException(InvalidOptionsException::class);
        (new HashOptionsResolver())->resolve([
            'secret' => '',
        ]);
    }

    public function testExceptionIsThrownWhenHashAlgoIsNotSupported()
    {
        $this->expectException(InvalidOptionsException::class);
        (new HashOptionsResolver())->resolve([
            'secret' => '42',
            'hash_algo' => 'thisIsNotAHashAlgorithm',
        ]);
    }

    public function testExceptionIsThrownWhenInvalidOptionPassed()
    {
        $this->expectException(UndefinedOptionsException::class);
        (new HashOptionsResolver())->resolve([
            'secret' => '42',
            'not_an_option' => 1337,
        ]);
    }

    /**
     * @dataProvider getBooleanOptions
     */
    public function testHashFlagsMustBeBoolean(string $booleanOptionKey)
    {
        $invalidValues = [null, 'true', '', new \stdClass()];
        foreach ($invalidValues as $invalidValue) {
            $this->expectException(InvalidOptionsException::class);
            (new HashOptionsResolver())->resolve([
                'secret' => '42',
                $booleanOptionKey => $invalidValue
            ]);
        }
    }

    public function getInvalidSecretValues()
    {
        yield [null];
        yield [42];
        yield [new \stdClass()];
    }

    public function getInvalidBooleanValues()
    {
        yield [null];
        yield ['true'];
        yield [''];
        yield [new \stdClass()];
    }

    public function getBooleanOptions()
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