<?php
/** @var \Composer\Autoload\ClassLoader $classLoader */
$classLoader = include './vendor/autoload.php';
$classLoader->addPsr4('HashedUriTest\\Utility\\', 'tests/Utility');