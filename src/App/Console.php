<?php

namespace SNOWGIRL_CORE\App;

use SNOWGIRL_CORE\App;
use Throwable;

class Console extends App
{
    public function run()
    {
        $argv = func_get_args();

        $this->setErrorHandler()
            ->setExceptionHandler()
            ->setShutdownHandler();

        $this->services->logger->setName('console')->enable();

        $this->onErrorLog()
            ->logRequest();

        $this->request->setController('console');

        array_shift($argv);

        $this->request->setAction(array_shift($argv));

        foreach (array_values($argv) as $k => $v) {
            $this->request->set('param_' . ($k + 1), $v);
        }

        try {
            $this->runAction();
        } catch (Throwable $ex) {
            $this->services->logger->makeException($ex);
            echo PHP_EOL . implode(PHP_EOL, [
                    get_class($ex),
                    $ex->getMessage(),
                    $ex->getTraceAsString()
                ]);
        }

        $text = $this->response->getBody();
        echo PHP_EOL;
        echo $text;
        echo PHP_EOL;
        $this->services->logger->make($text);

//        $this->logPerformance();
    }
}