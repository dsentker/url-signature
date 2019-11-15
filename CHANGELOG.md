# CHANGELOG #

### 1.0.10
* Added typehint in array docblocks 
* Small fixes for a better code style
* Fixed typos

### 1.0.9
* Fixed Issue #2 where a different order of query string parameters causes invalid hashes
* Added return type declarations
* Version bump

### 1.0.8
* Renamed variable for a more meaningful name
* Added missing test for symfony-style URL structure

### 1.0.7
* Some Code style fixed (PSR)
* Removed unnecessary docblocks
* Added return type declarations for some methods

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
First stable release.
* Fixed bug in Validator::isValid where an non-equal hash returns true, too (Do not use previous versions!)
* Updated readme file (removing old method names)
* Preparation for Symfony Bundle integration

### 1.0.1
* Travis CI Integration
* Fixed typos in readme

### 1.0.0
Initial release.
* Ported psecio/uri to an new library with PHP 7.x support
* Added new tests
* Packagist integration
* Renamed repository from "hasheduri" to "url-signature" 