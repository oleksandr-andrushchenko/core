<?php

namespace SNOWGIRL_CORE;

class Video
{
    protected $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function getLink()
    {
        return $this->file;
    }

    /**
     * @return mixed
     */
    public function stringify()
    {
        return $this->getLink();
    }

    public function __toString()
    {
        return $this->stringify();
    }
}
