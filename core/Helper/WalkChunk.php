<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 15.01.16
 * Time: 09:24
 * To change this template use File | Settings | File Templates.
 */
namespace SNOWGIRL_CORE\Helper;

/**
 * Class WalkChunk
 * @package SNOWGIRL_CORE\Helper
 */
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

    public function setFnGet(\Closure $v)
    {
        $this->fnGet = $v;
        return $this;
    }

    protected $fnDo;

    public function setFnDo(\Closure $v)
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
