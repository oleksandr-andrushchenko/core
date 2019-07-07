<?php

namespace SNOWGIRL_CORE\Service;

trait ToggleTrait
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