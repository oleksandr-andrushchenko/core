<?php

namespace SNOWGIRL_CORE\Console;

use SNOWGIRL_CORE\AbstractApp;
use Throwable;

/**
 * Class ConsoleApp
 *
 * @property ConsoleRequest request
 * @property ConsoleResponse response
 *
 * @package SNOWGIRL_CORE\Http
 */
class ConsoleApp extends AbstractApp
{
    public function logRequest()
    {
        $this->container->logger->debug(implode(' ', [
            $this->request->getController() . ':' . $this->request->getAction(),
            'with',
            implode(' ', $this->request->getParams())
        ]));
    }

    public function run()
    {
        parent::run();

        $argv = func_get_args();

        $this->setErrorHandler()
            ->setExceptionHandler()
            ->setShutdownHandler();

        $this->request->setController('console');

        array_shift($argv);

        $this->request->setAction(array_shift($argv));

        foreach (array_values($argv) as $k => $v) {
            $this->request->set('param_' . ($k + 1), $v);
        }

        $this->logRequest();

        try {
            $this->runAction();
        } catch (Throwable $e) {
            $this->container->logger->error($e);
            echo PHP_EOL . implode(PHP_EOL, [
                    get_class($e),
                    $e->getMessage(),
                    $e->getTraceAsString()
                ]);
        }

        $text = $this->response->getBody();
        echo PHP_EOL;
        echo $text;
        echo PHP_EOL;
        $this->container->logger->debug($text);

//        $this->logPerformance();
    }
}