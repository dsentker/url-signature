## UrlSignature  
**Create URLs with a signature to prevent modification**

![PHP from Packagist](https://img.shields.io/packagist/php-v/dsentker/url-signature.svg)
![Packagist](https://img.shields.io/packagist/v/dsentker/url-signature.svg)
[![Build Status](https://travis-ci.org/dsentker/url-signature.svg?branch=master)](https://travis-ci.org/dsentker/url-signature)
![GitHub last commit](https://img.shields.io/github/last-commit/dsentker/url-signature.svg)

This small PHP >7.2.0 library allows developers to build urls with a hash to prevent the modification of URL parts.   

## Why?
A common attack method that pentesters and actual attackers will use is to capture a URL with "id" values in it (like `/user/view?id=1234`) and manually change this value to try to bypass authorization checks. While an application should always have some kind of auth check when the URL is called, there's another step that can help to prevent URL changes: a signature value.

This signature value is built using the contents of the current URL along with a "secret" value unique to the application. This signature is then appended to the URL and can be used directly in links. When the URL is used and the request is received, the signature is then checked against the current URL values. If there's no match, the check fails.

## Installation
Installing via [Composer](https://getcomposer.org) is simple:

`composer require dsentker/url-signature`

If you do not like composer, you can download this repository and use any PSR-4-Autoloader to get everything loaded.

There is also [a **Symfony bundle**](https://github.com/dsentker/url-signature-bundle/) available:

`composer require dsentker/url-signature-bundle` 

## Usage
To sign (or validate) URLs, a key is required (which is of course secret). The key is used to hash special parts* of the URL and attach them as a signature in the query string of the URL.

_*) You can decide for yourself which parts of the URL should be hashed._

Later, on validation, the same key is used to hash the current URL. This hash is compared with the hash in query string.

### Signing URLs
```php
<?php
require_once 'vendor/autoload.php';

use UrlSignature\Builder;
use UrlSignature\HashConfiguration;

// Secret is loaded from a configuration outside of the library
$configuration = new HashConfiguration($_ENV['SECRET']);
$builder = new Builder($configuration);
$url = $builder->signUrl('https://example.com/foo?bar=42'); // http://example.com?foo?bar=42&_signature=90b7ac1...
```

In this example we've created a new `Builder` instance with a configuration object, and using it to create the URL based on the data and URL provided. The `$url` result has the signature (the hash) value appended to the query string.

### Verifying URLs
The other half of the equation is the verification of a URL. The library provides a `Validator` class to help with that. The validator must be initiated with the same configuration as the builder. Otherwise different hash values would be calculated.

```php
<?php
use UrlSignature\Validator;
$validator = new Validator($configuration); // Use the same $configuration here
var_dump($validator->isValid('https://example.com/foo?bar=42&_signature=90b7ac1...')); // returns true or false, depending on the signature

// If you want to catch Exceptions to determine the cause of an invalid URL, use Validator::verify() instead
$validator->verify('http://example.com?foo=this+is+a+test&_signature=90b7ac1...'); // Returns true or a \UrlSignature\Exception\ValidationException.
```

`Validator::isValid($url)` returns a boolean value based on the validation result, nothing more.
`Validator::verify($url)` Will throw some of these exceptions if the url signature (or the expiration parameter) is not valid:
* ValidationException
  * SignatureNotFoundException (if not present in query string)
  * SignatureInvalidException (if present, but empty or invalid)
  * SignatureExpiredException (if the expiration parameter has expired, never thrown if expiration was not part of the signature)

### Expiring URLs
The library also provides the ability to create URLs that will fail validation because they've expired. To make use of this, simply pass in a second parameter for the `signUrl()` method call. This value should either be a relative string (parsable by PHPs [strtotime](https://php.net/strtotime)) or a \DateTime object:
```php
<?php
$builder = new Builder($configuration);
$url = $builder->signUrl('http://example.com/foo?bar=42', '+10 minutes'); // https://example.com?foo=bar&_expires=1521661473&_signature=009e2d70...
```

You'll notice the addition of a new URL parameter, the `_expires` value. This value is automatically read when the `validate` call is made to ensure the URL hasn't timed out. If it has, even if the rest of the data is correct, the result will be `false`.

Even if the attacker tries to update the `_expires` date to extend the length of the URL, the validation will fail as that's not the `_expires` value it was originally hashed with.

### Advanced Configuration
#### Rename query parameter 
The URL query keys "_expires" and "_signature" can be modified with the configuration object:
```php
<?php
$querySignatureName = '_hash';
$queryExpiresName = 'ttl';
$configuration = new \UrlSignature\HashConfiguration('my-secret-key', $querySignatureName, $queryExpiresName);
$hashedUrl = (new Builder($configuration))->signUrl('https://example.com/?id=1234', new \DateTime('MONDAY NEXT WEEK'));
var_dump($hashedUrl); // https://example.com/?id=1234&ttl=123456789&_hash=009e2d70...
```
#### Control what parts of URL to sign
Per default, the following parts of the URL are considered for hash generation:
* Host (example.com)
* Path (/foo/bar)
* Query String (?qux=baz)

The configuration allows to modify these components with Bitmask Contants. Use `HashConfiguration::setHashMask()` to pass the Flags  (use the pipe | to join flags).

For example, consider to add the URL scheme to the hashing process. That means that the validation of an URL fails only if the protocols (https <-> http) has changed.

```php
<?php
use UrlSignature\HashConfiguration;
$config = new HashConfiguration('secret');
// Complete example: Use *ALL* parts of the URL for hashing
$config->setHashMask(
            HashConfiguration::FLAG_HASH_SCHEME
            | HashConfiguration::FLAG_HASH_HOST
            | HashConfiguration::FLAG_HASH_PORT
            | HashConfiguration::FLAG_HASH_PATH
            | HashConfiguration::FLAG_HASH_QUERY
            | HashConfiguration::FLAG_HASH_FRAGMENT
        );
```

## A Word about Security ##
This library creates a hash from one or more URL parts. That means, that the URL is only valid if the given signature matches the current signature. Like any other hash event (for example, hashing passwords), this is considered quite secure. For a signed URL to be truly secure, the developer must ensure that (when receiving a request) a check is made as to whether a hash check is required and / or whether the submitted signature is correct. In other words, **if the user has the ability to retrieve a URL without a signature, the best hashing algorithm will not work**.

### Do NOT use this library if... ###
* ...you are unsure whether the processing part of the request (e.g., the controller) can check the hash at any time.
* ...your goal is to prevent the distribution of URLs to unauthorized persons (that is not the purpose of this library!)
* ...this library is the only auditing mechanism designed to prevent a user from retrieving content that is not intended for them.

## Credits
Based on the ideas by [psecio](https://github.com/psecio), the project was forked by [dsentker](https://github.com/dsentker) (that's me 😁) to upgrade the code for PHP 7.x applications. The adjustments then resulted in a separate library (this one) and a symfony bundle.

## Dependencies
The library uses the URL functions of [thephpleague/uri](https://github.com/thephpleague/uri) to parse, extract and (re-)build URL components. For unit tests, PhpUnit is used. 

## Submitting bugs and feature requests
Bugs and feature request are tracked on GitHub.

## Symfony 4 Bundle
I also created [a bundle for Symfony4](https://github.com/dsentker/url-signature-bundle/) (with twig support and annotations).  

## Testing
`./vendor/bin/phpunit`

Do not be surprised about a short break of two seconds during the tests. A `sleep(2)` was built in to test for the validation of the timeout functionality.

## See also
* [URL Signature Bundle](https://github.com/dsentker/url-signature-bundle/) for Symfony 4
* [ivanakimov/hashids](https://github.com/ivanakimov/hashids.php) A PHP Version from [hashids.org](https://hashids.org/)
* [HashidsBundle](https://github.com/roukmoute/HashidsBundle) The relating Symfony Bundle from _roukmoute_