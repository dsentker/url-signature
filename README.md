# UrlFingerprint

A library that creates a unique hash value from a URL - without getting a headache.

## The problem

Generating a unique hash from a URL is useful, e.g. when caching API responses or for security purposes. The simple idea
of just hashing the URL can turn into a difficult task. Consider the following example:

```php
$url = 'https://example.com/index.php?foo=bar&qux';
$urlHash = hash('md5', $url);
```

This creates the same hash value (digest) each time. But what if the order of the query string parameters is changed?

```php
$url = 'https://example.com/index.php?qux&foo=bar';
$urlHash = hash('md5', $url);
```

The URL and the query parameters are technically the same, but the generated hash is different. There are more parts of
the URL that you may not want to use for the hash algorithm:

```php
$url = 'https://example.com/#logo'; // Anchor / fragment appended
$url = 'http://example.com/'; // Different protocol
$url = 'https://example.com/?'; // Empty query string
```

All three URLs could be similar for your requirements and should therefore also generate the same hash result. This is
what this library was built for.

## The solution

This library aims to get a hash without side effects. Get the same hash of a URL, ignore negligible parts of the URL and
don't care about the order of query string parameters. This library uses the concept of a "Fingerprint". Depending on
your settings, a specific fingerprint is generated for each URL, which contains the digest, the hash algorithm and the
hashed parts of the URL.

## Basic Usage

```php
$reader = new \UrlFingerprint\FingerprintReader([
    'secret' => 's3cre7v4lu3',
]);
$fingerprint1 = $reader->capture('http://www.example.com/info?id=42&details#foo');
echo $fingerprint1->getDigest(); // d7335d0a237f47a049415a780c4e1c96
echo $fingerprint1->getHashAlgo(); // 'sha256'

// different query string order, fragment is missing
$fingerprint2 = $reader->capture('http://www.example.com/info?details&id=42'); 
echo $fingerprint2->getDigest(); // d7335d0a237f47a049415a780c4e1c96 - the same

$reader->compare($fingerprint1, $fingerprint2); // true
```

## Installation and requirements
```bash
composer require dsentker/url-fingerprint
```
Composer 2 and PHP >= 7.4 is required.

## Options
Configure options in the  constructor to specify the way the digest is created:

Option | Type | Default | Description
--- | --- | --- | ---
**`secret`** | string |  | **(required!)** Choose a secret key for the `hash_hmac`function.
**`hash_algo`**  | string | sha256 | A [hashing algorithm suitable for `hash_hmac()`](https://www.php.net/manual/de/function.hash-hmac-algos.php).
**`hash_scheme`** | boolean | true | Whether to hash the scheme part of the url (https://, ftp:// etc.)
**`hash_userinfo`** | boolean | true | Whether to hash the [user information](https://www.ietf.org/rfc/rfc2396.txt) part of the url (e.g. userinfo@host)
**`hash_host`** | boolean | true | Whether to hash the host name or not
**`hash_port`** | boolean | false | Whether to hash the port
**`hash_path`** | boolean | true | Whether to path of the URL (e.g. _/foo/index.php_)
**`hash_query`** | boolean | true | Whether to hash the query string parts in the URL (Keys _and_ values). 
**`hash_fragment`** | boolean | false | Whether to hash the fragment / hash suffix in the URL or not. 

### Example
```php
$reader = new \UrlFingerprint\FingerprintReader([
    'secret' => 's3cre7v4lu3',
    'hash_host' => false,
]);
$fingerprint1 = $reader->capture('http://www.example.com/?foo');
$fingerprint2 = $reader->capture('http://www.example.net/?foo');
// Different hosts, but not part of the fingerprint. So both digest values are equal.
$reader->compare($fingerprint1, $fingerprint2); // true
```

```php
$reader = new \UrlFingerprint\FingerprintReader([
    'secret' => 's3cre7v4lu3',
    'hash_fragment' => true,
]);
$fingerprint1 = $reader->capture('https://www.example.com/?foo');
$fingerprint2 = $reader->capture('https://www.example.com/?foo#bar');
// Same URL except the fragment
$reader->compare($fingerprint1, $fingerprint2); // false
```

## Testing
`$ ./vendor/bin/phpunit tests`

## Contributing
If you notice bugs, have general questions or want to implement a feature, you are welcome to collaborate.

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Background
This library replaces the predecessor, the [url-signature library](https://github.com/dsentker/url-signature).

### Motivation on rebuild
Compared to the url-signature library, this library is rewritten completely and should solve the following drawbacks:

- The url-signature library did not follow the Single-responsibility principle
- Array in query string could lead to unexpected results
- I do not like setting options with bitmask flags
- A thin wrapper for normalizing query strings is no longer needed
- Better debug possibilities when comparing hash values.