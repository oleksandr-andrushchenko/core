#!/usr/bin/env php
<?php
$root = __DIR__;
$app = require $root . '/vendor/snowgirl-core/boot.php';
$app->runCmd($argv);
