# UrlFingerprint

**A library that creates a unique hash value from a URL - without getting a headache.**

This library aims to get a unique but consistent _digest_ (hash result) from a URL with or without side effects. It
generates equal hash results even if the parameter order in the query string is different or the URL contains encoded
characters like `%20`.

This library uses the concept of a "Fingerprint". Depending on your settings, a specific fingerprint is generated for
each URL, which contains the digest, the hash algorithm and the hashed parts of the URL.

## Basic Usage

```php
$reader = new FingerprintReader([
    'secret' => 's3cre7v4lu3',
]);
$fingerprint1 = $reader->capture('http://www.example.com/info?id=42&details');

// Same URL, but different parameter order in query string.
$fingerprint2 = $reader->capture('http://www.example.com/info?details&id=42'); 

$reader->compare($fingerprint1, $fingerprint2); // bool(true)

echo $fingerprint1->digest; // d7335d0a237f47a049415a780c4e1c96
echo $fingerprint2->digest; // d7335d0a237f47a049415a780c4e1c96 - the same
```

## Installation & Usage

[Composer 2](https://getcomposer.org/2) and PHP >= 7.4 is required.

Install url-fingerprint with composer.

```bash
composer require dsentker/url-fingerprint
```

```php
# Create a FingerprintReader instance and provide options in the
# constructor (the `secret` parameter is required).
$reader = new \UrlFingerprint\FingerprintReader([
    'secret' => '42',
    'hash_algo' => 'md5'
]);

# Capture a new fingerprint by given URL
$fingerprint = $reader->capture('http://www.github.com/');

echo $fingerprint->digest; // the result from the hash process
echo $fingerprint->hashAlgo; // md5
echo $fingerprint->gist; // a JSON representation of the url parts

# Use the compare method to test against another fingerprint 
$reader->compare($fingerprint, $reader->capture('http://github.com'));
```

### Options

Configure options in the constructor to specify the way the digest is created:

Option | Type | Default | Description
--- | --- | --- | ---
**`secret`** | string |  | **(required!)** Choose a secret key for the `hash_hmac`function.
**`hash_algo`**  | string | sha256 | A [hashing algorithm suitable for `hash_hmac()`](https://www.php.net/manual/de/function.hash-hmac-algos.php).
**`ignore_scheme`** | boolean | false | Whether to hash the scheme part of the url (https://, ftp:// etc.)
**`ignore_userinfo`** | boolean | false | Whether to hash the [user information](https://www.ietf.org/rfc/rfc2396.txt) part of the url (e.g. userinfo@host)
**`ignore_host`** | boolean | false | Whether to hash the host name or not
**`ignore_port`** | boolean | false | Whether to hash the port
**`ignore_path`** | boolean | false | Whether to path of the URL (e.g. _/foo/index.php_)
**`ignore_query`** | boolean | false | Whether to hash the query string parts in the URL (Keys _and_ values).
**`ignore_fragment`** | boolean | false | Whether to hash the fragment / hash suffix in the URL or not.

### Examples

```php
$reader = new \UrlFingerprint\FingerprintReader([
    'secret' => 's3cre7v4lu3',
    'ignore_host' => false,
]);
// Different hosts, but not part of the fingerprint. So both digest values are equal.
$fingerprint1 = $reader->capture('http://www.example.com/?foo');
$fingerprint2 = $reader->capture('http://www.example.net/?foo');
$reader->compare($fingerprint1, $fingerprint2); // true
```

```php
$reader = new \UrlFingerprint\FingerprintReader([
    'secret' => 's3cre7v4lu3',
    'ignore_fragment' => true,
]);
// Create fingerprints for two same URLs except the fragment
$fingerprint1 = $reader->capture('https://www.example.com/?foo');
$fingerprint2 = $reader->capture('https://www.example.com/?foo#bar');

// Fingerprints are not the same - The fragment part of the URL is taken into account. 
$reader->compare($fingerprint1, $fingerprint2); // false
```

```php
// Define query string keys which should be ignored and pass it as 2nd argument in the capture method.
$ignoreQuery = ['foo', 'baz'];

$fingerprint1 = $reader->capture('https://www.example.com/detail');
$fingerprint2 = $reader->capture('https://www.example.com/detail?foo=bar', $ignoreQuery);

// Fingerprints are equal because the 'foo' parameter is ignored
$reader->compare($fingerprint1, $fingerprint2); // true
```

## Testing

With PHPUnit:
`$ ./vendor/bin/phpunit tests`

## Contributing

If you notice bugs, have general questions or want to implement a feature, you are welcome to collaborate.

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Background

### Motivation

Generating a unique hash from a URL is useful, e.g. when caching API responses or for security purposes. The simple idea
of just hashing the URL can turn into a difficult task. Consider the following example:

```php
$urlHash = hash('md5', 'https://example.com/index.php?foo=bar&qux');
```

This creates the same hash value (digest) each time. But what if the order of the query string parameters is changed?

```php
$url1 = 'https://example.com/index.php?foo=bar&qux';
$url2 = 'https://example.com/index.php?qux&foo=bar';

hash_equals(
    hash('md5', $url1),
    hash('md5', $url2)
); // false :-(
```

Both URLs are technically the same, but the generated digest is different.

There are more parts of a URL that you may not want to include for the hash algorithm:

```php
$url = 'https://example.com/#logo'; // Anchor / fragment appended
$url = 'http://example.com/'; // Different protocol
$url = 'https://example.com/?'; // Empty query string / No key/value groups
```

All three URLs could be similar according to your requirements and should therefore generate the same hash result. This
is what this library was built for. There are other things in a URL that shouldn't affect the hash value of a URL:

- The order of the query parameters is different
- Another protocol is used
- URL-encoded characters such as `%20` should be taken into account

### Predecessor

This library replaces the predecessor, the [url-signature library](https://github.com/dsentker/url-signature).

Compared to the url-signature library, this library is rewritten completely and should solve the following drawbacks:

- The url-signature library did not follow the Single-responsibility principle
- Arrays in query string could lead to unexpected results
- I do not like setting options with bitmask flags
- A thin wrapper for normalizing query strings is not required
- Insufficient investigation possibilities to debug the hash process
