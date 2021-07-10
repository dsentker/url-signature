# urlhasher
A library that creates a fingerprint of a URL - regardless of the order of query string parameters.

__· !WIP! ·__

## Basic Usage

```php
$reader = new \UrlFingerprint\FingerprintReader([
    'secret' => 's3cre7v4lu3',
]);
$fingerprint1 = $reader->capture('http://www.example.com/info?id=42&details');
echo $fingerprint1->getHash(); // d7335d0a237f47a049415a780c4e1c96
echo $fingerprint1->getHashAlgo(); // 'sha-256'

$fingerprint2 = $reader->capture('http://www.example.com/info?details&id=42'); // different query string order
echo $fingerprint2->getHash(); // d7335d0a237f47a049415a780c4e1c96 - the same
$reader->compare($fingerprint1, $fingerprint2); // true
```