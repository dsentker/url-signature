# CHANGELOG #

## 1.x
02/2019 - 03/2019

### 1.0.6
* Fixing issue #1 where an expiring query value was not part of the hash and fails on validation.
* Added tests for #1.

### 1.0.5
* Fixed typos in Docs
* Get rid of old repository name
* Added an information for the Symfony bundle.

### 1.0.4
* Enabled argument unpacking for bitmask via HashConfiguration->setHashMask() to facilitate the configuration in symfony's services.yaml

### 1.0.3
* Validator::verify will now also throw an exception if the hash does not match the calculated hash (this will prevent using throw-catch statements prepended by a boolean comparison)

### 1.0.2
First stable relase.
* Fixed bug in Validator::isValid where an non-equal hash returns true, too (Do not use previous versions!)
* Updated readme file (removing old method names)
* Preparements for Symfony Bundle integration

### 1.0.1
* Travis CI Integration
* Fixed typos in readme

### 1.0.0
Initial release.
* Ported psecio/uri to an new library with PHP 7.x support
* Added new tests
* Packagist integration
* Renamed repository from "hasheduri" to "url-signature" 