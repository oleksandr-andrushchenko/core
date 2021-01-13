<?php


namespace SNOWGIRL_CORE;

use Psr\Log\LoggerInterface;

/**
 * @property LoggerInterface logger
 */
trait DebuggerTrait
{
    private function debug(string $fn, array $args = [], callable $job = null)
    {
        $start = microtime(true);

        $this->logger->debug($fn, [
            'args' => $args,
//            'start' => $start,
        ]);

        $output = $job ? $job(...$args) : parent::$fn(...$args);
        $finish = microtime(true);
        $duration = substr($finish - $start, 0, 7);

        $this->logger->debug($fn, [
//            'finish' => $finish,
            'return' => $output,
            'duration' => $duration,
        ]);

        return $output;
    }
}