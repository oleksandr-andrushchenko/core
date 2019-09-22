<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\App;

trait OutputTrait
{
    protected function output($text, App $app)
    {
        $text = is_array($text) ? implode(PHP_EOL, $text) : $text;

        echo PHP_EOL;
        echo $text;
        echo PHP_EOL;
        $app->services->logger->make($text);
        return true;
    }
}

