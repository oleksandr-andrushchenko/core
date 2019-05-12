<?php

use SNOWGIRL_CORE\App;

function T()
{
    return call_user_func_array([App::$instance->trans, 'makeText'], func_get_args());
}

function trans()
{
    return T(...func_get_args());
}

function L()
{
    return call_user_func_array([App::$instance->router, 'makeLink'], func_get_args());
}

function href()
{
    return L(...func_get_args());
}

function D()
{
    echo '<pre>';
    $args = func_get_args();
    var_dump(1 == count($args) ? $args[0] : $args);
    die;
}

function dump($var)
{
    D(...func_get_args());
}