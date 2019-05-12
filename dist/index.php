<?php

use APP\App;

/** @noinspection PhpIncludeInspection */
$loader = require __DIR__ . '/../vendor/autoload.php';
App::getInstance($loader)->runWww();