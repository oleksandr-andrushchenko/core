<?php
/** @noinspection PhpIncludeInspection */
$loader = require __DIR__ . '/../vendor/autoload.php';
$app = new \APP\Http\HttpApp($loader);
$app->run();