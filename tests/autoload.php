<?php /** @noinspection PhpIncludeInspection */

/** @var \Composer\Autoload\ClassLoader $classLoader */
$classLoader = require realpath(__DIR__ . '/../vendor/autoload.php');
$classLoader->addPsr4('UrlSignatureTest\\Utility\\', 'tests/Utility');