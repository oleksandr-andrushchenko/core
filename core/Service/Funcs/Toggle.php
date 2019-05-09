<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 11/9/17
 * Time: 3:51 PM
 */
namespace SNOWGIRL_CORE\Service\Funcs;

/**
 * Class Toggle
 * @package SNOWGIRL_CORE\Service\Funcs
 */
trait Toggle
{
    protected $enabled;

    public function isOn()
    {
        return $this->enabled;
    }

    public function enable()
    {
        $this->enabled = true;
        return $this;
    }

    public function disable()
    {
        $this->enabled = false;
        return $this;
    }
}