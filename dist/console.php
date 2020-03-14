#!/usr/bin/env php
<?php
/** @noinspection PhpIncludeInspection */
$loader = require __DIR__ . '/../vendor/autoload.php';
$app = new \APP\Console\ConsoleApp($loader);
$app->run(...$argv);