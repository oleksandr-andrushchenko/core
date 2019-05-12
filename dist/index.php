<?php

use APP\App\Web;

/** @noinspection PhpIncludeInspection */
$loader = require __DIR__ . '/../vendor/autoload.php';
Web::getInstance($loader)->run();