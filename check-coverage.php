#!/usr/bin/php
<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/tests/Support/CoverageException.php');
require_once(__DIR__ . '/tests/Support/Coverage.php');

use BlueBillywig\Tests\Support\Coverage;
use BlueBillywig\Tests\Support\CoverageException;

try {
    $coverage = new Coverage();
    $coverage->check();
} catch (CoverageException $ce) {
    echo '::error::COVERAGE ERROR: ' . $ce->getMessage() . PHP_EOL;
    exit(1);
}
echo 'Coverage amount is sufficient' . PHP_EOL;
