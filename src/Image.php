<?php

namespace SNOWGIRL_CORE;

class Image
{
    protected $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function getFile()
    {
        return $this->file;
    }

    protected $isLocalHash;

    public function setIsLocalHash(bool $isLocalHash)
    {
        $this->isLocalHash = $isLocalHash;

        return $this;
    }

    public function getIsLocalHash()
    {
        return $this->isLocalHash;
    }

    protected $info;

    public function setInfo(array $info)
    {
        $this->info = $info;

        return $this;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function __toString()
    {
        return $this->file;
    }
}
