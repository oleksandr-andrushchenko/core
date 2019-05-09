<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 11/10/16
 * Time: 3:38 AM
 */
namespace SNOWGIRL_CORE\Ext;

/**
 * Class Observer
 * @package SNOWGIRL_CORE\Ext
 */
trait Observer
{
    protected $callback = [];

    public function on($event, \Closure $callback)
    {
        if (!isset($this->callback[$event])) {
            $this->callback[$event] = [];
        }

        $this->callback[$event][] = $callback;
        return $this;
    }

    public function off($event)
    {
        $this->callback[$event] = [];
        return $this;
    }

    public function trigger($event)
    {
        if (isset($this->callback[$event])) {
            foreach ($this->callback[$event] as $fn) {
                $fn($this);
            }
        }

        $this->off($event);
        return $this;
    }
}