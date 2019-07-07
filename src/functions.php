<?php

use SNOWGIRL_CORE\App;

function trans()
{
    return call_user_func_array([App::$instance->trans, 'makeText'], func_get_args());
}

function href()
{
    return call_user_func_array([App::$instance->router, 'makeLink'], func_get_args());
}

function dump($var)
{
    echo '<pre>';
    $args = func_get_args();
    var_dump(1 == count($args) ? $args[0] : $args);
    die;
}