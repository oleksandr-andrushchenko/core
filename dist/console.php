#!/usr/bin/env php
<?php

use APP\App\Console;

/** @noinspection PhpIncludeInspection */
$loader = require __DIR__ . '/../vendor/autoload.php';
Console::getInstance($loader)->run($argv);