<?php

namespace SNOWGIRL_CORE\Controller\Console;

use SNOWGIRL_CORE\AbstractApp;

trait OutputTrait
{
    protected function output($text, AbstractApp $app)
    {
        $text = is_array($text) ? implode(PHP_EOL, $text) : $text;

        echo PHP_EOL;
        echo $text;
        echo PHP_EOL;
        $app->container->logger->debug($text);

        return true;
    }
}

