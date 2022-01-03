<?php

namespace UrlFingerprint;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class FingerprintOptionsResolver extends OptionsResolver
{
    public function __construct()
    {
        $booleanOptionKeys = [
            'ignore_scheme'   => false,
            'ignore_userinfo' => false,
            'ignore_host'     => false,
            'ignore_port'     => false,
            'ignore_path'     => false,
            'ignore_query'    => false,
            'ignore_fragment' => false,
        ];

        foreach ($booleanOptionKeys as $optionKey => $defaultValue) {
            $this->setDefault($optionKey, $defaultValue);
            $this->setAllowedTypes($optionKey, ['bool']);
        }

        $this->define('secret')
            ->required()
            ->allowedValues(function ($value) {
                return mb_strlen($value) > 0;
            })
            ->allowedTypes('string');

        $this->define('hash_algo')
            ->default('sha256')
            ->required()
            ->allowedValues(function ($value) {
                return in_array($value, hash_hmac_algos(), true);
            })
            ->allowedTypes('string');
    }
}
