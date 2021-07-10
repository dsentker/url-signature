<?php

use League\Uri\Http;
use League\Uri\Uri;
use League\Uri\UriModifier;

require './vendor/autoload.php';

$urlToTest = 'http://example.com/?b=x&c=x&b=x';

$uri = Http::createFromString($urlToTest);
echo $uri->getQuery() . PHP_EOL;
$newUri = UriModifier::sortQuery($uri);
echo $newUri->getQuery() . PHP_EOL;

$uriString = "http://example.com/?c=toto&a=bar%20baz&b=ape";
$uri = Http::createFromString($uriString);
echo $uri->getQuery() . PHP_EOL;    //display "kingkong=toto&foo=bar%20baz&kingkong=ape"

$newUri = UriModifier::sortQuery($uri);
echo $newUri->getQuery() . PHP_EOL; //display "kingkong=toto&kingkong=ape&foo=bar%20baz"