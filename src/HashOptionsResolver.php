<?php
namespace UrlHasher;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class HashOptionsResolver extends OptionsResolver
{

    public function __construct()
    {
        $booleanOptionKeys = [
            'hash_scheme'   => true,
            'hash_userinfo' => true,
            'hash_host'     => true,
            'hash_port'     => false,
            'hash_path'     => true,
            'hash_query'    => true,
            'hash_fragment' => false,
        ];

        foreach ($booleanOptionKeys as $optionKey => $defaultValue) {
            $this->setDefault($optionKey, $defaultValue);
            $this->setAllowedTypes($optionKey, ['bool']);
        }

        $this->define('secret')
            ->required()
            ->allowedValues(function($value) {
                return mb_strlen($value) > 0;
            })
            ->allowedTypes('string');

        $this->define('hash_algo')
            ->default('sha_256')
            ->required()
            ->allowedValues(function($value) {
                return in_array($value, hash_hmac_algos(), true);
            })
            ->allowedTypes('string');
    }

}