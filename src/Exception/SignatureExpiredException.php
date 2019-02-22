<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 22.02.2019
 * Time: 13:51
 */

namespace HashedUri\Exception;

class SignatureExpiredException extends ValidationException
{
    public static function timeoutViolation(): SignatureExpiredException
    {
        return new static('Signature has expired and is no longer valid!');
    }
}