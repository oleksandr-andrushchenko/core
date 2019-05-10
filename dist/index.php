#!/usr/bin/env php
<?php

use SNOWGIRL_CORE\App;

/** @noinspection PhpIncludeInspection */
$loader = require __DIR__ . '/../vendor/autoload.php';
App::getInstance($loader)->runWww();