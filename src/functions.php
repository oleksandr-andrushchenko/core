<?php

function dump($var)
{
    echo '<pre>';
    $args = func_get_args();
    var_dump(1 == count($args) ? $args[0] : $args);
    die;
}

function profile($text, callable $fn)
{
    $s = new DateTime;
    $output = $fn();
    $e = new DateTime;
    echo PHP_EOL . $text . ':' . PHP_EOL . $s->diff($e)->format('%H:%I:%S') . PHP_EOL;

    return $output;
}