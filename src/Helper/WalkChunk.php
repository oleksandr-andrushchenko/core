<?php

namespace SNOWGIRL_CORE\Helper;

class WalkChunk
{
    private $page;
    private $size;
    private $fnGet;
    private $fnDo;

    public function __construct(int $size = 1000, int $page = 1)
    {
        $this->page = $page;
        $this->size = $size;
    }

    public function setFnGet(callable $v): WalkChunk
    {
        $this->fnGet = $v;
        return $this;
    }

    public function setFnDo(callable $v): WalkChunk
    {
        $this->fnDo = $v;
        return $this;
    }

    public function run(string &$msg = null): bool
    {
        do {
            $items = call_user_func($this->fnGet, $this->page, $this->size);

            if (!$count = count($items)) {
                break;
            }

            if (false === call_user_func($this->fnDo, $items)) {
                break;
            }

            $this->page++;
        } while ($count == $this->size);

        $msg = 'completed';
        return true;
    }
}
