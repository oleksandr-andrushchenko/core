<?php

namespace SNOWGIRL_CORE\Helper;

/**
 * Improved version of WalkChunk
 * - uses last returned value (id) from fnDo instead of page (limit offset)
 * Class WalkChunk2
 * @package SNOWGIRL_CORE\Helper
 */
class WalkChunk2
{
    private $last;
    private $size;
    private $fnGet;
    private $fnDo;

    public function __construct(int $size = 1000, $last = null)
    {
        $this->last = $last;
        $this->size = $size;
    }

    public function setFnGet(callable $v)
    {
        $this->fnGet = $v;
        return $this;
    }

    /**
     * Function should returns lastId on each call
     * @param callable $v
     * @return $this
     */
    public function setFnDo(callable $v)
    {
        $this->fnDo = $v;
        return $this;
    }

    public function run(string &$msg = null): bool
    {
        do {
            $items = call_user_func($this->fnGet, $this->last, $this->size);

            if (!$count = count($items)) {
                break;
            }

            $last = call_user_func($this->fnDo, $items);

            if (false === $last) {
                break;
            }

            if (!$last) {
                $msg = 'invalid last value received from $this->fnDo';
                return false;
            }

            $this->last = $last;
        } while ($count == $this->size);

        $msg = 'completed';
        return true;
    }
}
