<?php

namespace SNOWGIRL_CORE\Helper;

class WalkChunk
{
    protected $page;
    protected $size;

    public function __construct($size = 1000, $page = 1)
    {
        $this->page = (int)$page;
        $this->size = (int)$size;
    }

    protected $fnGet;

    public function setFnGet(callable $v)
    {
        $this->fnGet = $v;
        return $this;
    }

    protected $fnDo;

    public function setFnDo(callable $v)
    {
        $this->fnDo = $v;
        return $this;
    }

    public function run(&$msg = null)
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
