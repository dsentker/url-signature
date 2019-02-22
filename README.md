## HashedUri  

**Sign URLs to prevent modification**

This small PHP >7.1 library allows developers to build urls with a hash to prevent the modification of URL parts.   

## Why?
A common attack method that pentesters and actual attackers will use is to capture a URL with "id" values in it (like `/user/view?id=1234`) and manually change this value to try to bypass authorization checks. While an application should always have some kind of auth check when the URL is called, there's another step that can help to prevent URL changes: a signature value.

This signature value is built using the contents of the current URL along with a "secret" value unique to the application. This signature is then appended to the URL and can be used directly in links. When the URL is used and the request is received, the signature is then checked against the current URL values. If there's no match, the check fails.

## Installation
Installing via [Composer](https://getcomposer.org) is simple:
`composer require dsentker/hashed-uri`  (WIP!)

## Usage
To sign (or validate) URLs, a key is required (which is of course secret). The key is used to hash special parts* of the URL and attach them as a signature in the query string of the URL.
*) You can decide for yourself which parts of the URL should be hashed.

Later, on validation, the same key is used to hash the current URL. This hash is compared with the hash in query string.

### Signing URLs
```php
<?php
require_once 'vendor/autoload.php';

use HashedUri\Builder;
use HashedUri\HashConfiguration;

// Secret is loaded from a configuration outside of the library
$configuration = new HashConfiguration($_ENV['SECRET']);
$builder = new Builder($configuration);
$url = $builder->hashUrl('http://example.com/foo?bar=42'); // http://example.com?foo?bar=42&_signature=90b7ac1...
```

In this example we've created a new `Builder` instance with a configuration object, and using it to create the URL based on the data and URL provided. The `$url` result has the signature (the hash) value appended to the query string.


### Verifying URLs
The other half of the equation is the verification of a URL. The library provides a `Validator` class to help with that. The validator must be initiated with the same configuration as the builder. Otherwise different hash values would be calculated.

```php
<?php
use HashedUri\Validator;
$validator = new Validator($configuration); // Use the same $configuration here
var_dump($validator->isValid('http://example.com?foo=this+is+a+test&_signature=90b7ac1...')); // returns true or false, depending on the signature

// If you want to catch Exceptions to determine the cause of an invalid URL, use Validator::verify() instead
$validator->verify('http://example.com?foo=this+is+a+test&_signature=90b7ac1...'); // Returns true or a \HashedUri\Exception\ValidationException.

```

`Validator::isValid($url)` returns a boolean value based on the validation result, nothing more.
`Validator::verify($url)` Will throw some of these exceptions if the url signature (or the timeout) is not valid:
* ValidationException
  * SignatureNotFoundException (if not present in query string)
  * SignatureInvalidException (if present, but empty)
  * SignatureExpiredException (if timeout has expired)

### Expiring URLs
The library also provides the ability to create URLs that will fail validation because they've expired. To make use of this, simply pass in a second parameter for the `hashUrl` method call. This value should either be a relative string (parsable by PHP's [strtotime](https://php.net/strtotime)) or a \DateTime object:
```php
<?php
$builder = new Builder($configuration);
$url = $builder->hashUrl('http://example.com/foo?bar=42', '+10 minutes');

// https://example.com?foo=bar&_expires=1521661473&_signature=009e2d70...
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
$configuration = new \HashedUri\HashConfiguration('my-secret-key', $querySignatureName, $queryExpiresName);
$hashedUrl = (new Builder($configuration))->hashUrl('https://example.com/?id=1234', new \DateTime('MONDAY NEXT WEEK'));
var_dump($hashedUrl); // https://example.com/?id=1234&ttl=123456789&_hash=009e2d70...
```
#### Control what parts of URL to hash
Per default, the following parts of the URL are considered for hash generation:
* Host (example.com)
* Path (/foo/bar)
* Query String (?qux=baz)

The configuration allows to modify these components with Bitmask Contants. Use `HashConfiguration::setHashConfig()` to pass the Flags  (use the pipe | to join flags).

For example, consider to add the URL scheme to the hashing process. That means that the validation of an URL fails only if the protocols (https <-> http) has changed.

```php
<?php
$config = new HashConfiguration('secret');
// Complete example: Use *ALL* parts of the URL for hashing
$config->setHashConfig(
            HashConfiguration::FLAG_HASH_SCHEME
            | HashConfiguration::FLAG_HASH_HOST
            | HashConfiguration::FLAG_HASH_PORT
            | HashConfiguration::FLAG_HASH_PATH
            | HashConfiguration::FLAG_HASH_QUERY
            | HashConfiguration::FLAG_HASH_FRAGMENT
        );
```

## Credits
Based on the ideas by [psecio](https://github.com/psecio), the project was forked by [dsentker](https://github.com/dsentker) (thats me 😁) to upgrade the code for PHP 7.x applications and many other improvements. The implementation of a Symfony Bundle is planned.

## Submitting bugs and feature requests
Bugs and feature request are tracked on GitHub.

## TODO
* Rename `HashConfiguration::setHashConfig()` method to a more meaningful name
* Make a small symfony bundle to use this with Twig and the request object.  

## Testing
`./vendor/bin/phpunit tests`

Do not be surprised about a short break of two seconds during the tests. A sleep(2) was built in to test for the validation of the timeout functionality.