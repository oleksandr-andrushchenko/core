<?php

namespace SNOWGIRL_CORE\Command;

class BatchCommand
{
    private $iterator;
    private $job;
    private $size;

    public function __construct(iterable $iterator, callable $job, int $size = 1000)
    {
        $this->iterator = $iterator;
        $this->job = $job;
        $this->size = (int) $size;
    }

    public function __invoke()
    {
        $aff = 0;
        $batch = 0;

        $items = [];

        foreach ($this->iterator as $item) {
            $items[] = $item;

            if ($this->size === count($items)) {
                if (false === call_user_func($this->job, $items, $batch)) {
                    break;
                }

                $aff += $this->size;

                $batch++;
                $items = [];
            }
        }

        if ($items) {
            call_user_func($this->job, $items, $batch);
            $aff += count($items);
        }

        return $aff;
    }
}
