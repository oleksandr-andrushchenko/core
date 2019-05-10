<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 01.09.17
 * Time: 22:27
 * To change this template use File | Settings | File Templates.
 */

namespace SNOWGIRL_CORE;

/**
 * Class Video
 * @package SNOWGIRL_CORE
 */
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
