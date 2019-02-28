## CHANGELOG ##

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
* Preparements 