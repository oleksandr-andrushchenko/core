<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 20.03.18
 * Time: 14:17
 * To change this template use File | Settings | File Templates.
 */
namespace SNOWGIRL_CORE\Helper;

/**
 * Improved version of WalkChunk
 * - uses last returned value (id) from fnDo instead of page (limit offset)
 *
 * Class WalkChunk2
 * @package SNOWGIRL_CORE\Helper
 */
class WalkChunk2
{
    protected $last;
    protected $size;

    public function __construct($size = 1000, $last = null)
    {
        $this->last = $last;
        $this->size = (int)$size;
    }

    protected $fnGet;

    public function setFnGet(\Closure $v)
    {
        $this->fnGet = $v;
        return $this;
    }

    protected $fnDo;

    /**
     * Function should returns lastId on each call
     *
     * @param \Closure $v
     * @return $this
     */
    public function setFnDo(\Closure $v)
    {
        $this->fnDo = $v;
        return $this;
    }

    public function run(&$msg = null)
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
