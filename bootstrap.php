<?php
if ( !file_exists($file = __DIR__.'/vendor/autoload.php')) {
    throw new RuntimeException('Install dependencies to run this script.');
}

$loader = require_once $file;

