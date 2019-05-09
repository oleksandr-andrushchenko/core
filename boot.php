<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 07.03.16
 * Time: 22:40
 * To change this template use File | Settings | File Templates.
 */

use SNOWGIRL_CORE\App;

/** @noinspection PhpIncludeInspection */
$loader = require $root . '/vendor/autoload.php';

function T()
{
    return call_user_func_array([App::$instance->translator, 'makeText'], func_get_args());
}

function L()
{
    return call_user_func_array([App::$instance->router, 'makeLink'], func_get_args());
}

function D()
{
    echo '<pre>';
    $args = func_get_args();
    var_dump(1 == count($args) ? $args[0] : $args);
    die;
}

$app = App::getInstance($loader, realpath($root), isset($ns) ? $ns : 'SNOWGIRL_CORE');
//$app->services->logger->get('error')->setAsErrorLog()->enable();
return $app;